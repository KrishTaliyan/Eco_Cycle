import './bootstrap';
import { createIcons, icons } from 'lucide';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const formatter = new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 });
const rupeeFormatter = new Intl.NumberFormat('en-IN');
const INDIA_BOUNDS = { minLat: 6.4, maxLat: 37.7, minLng: 68.0, maxLng: 97.5 };

const state = {
    analysis: null,
    dashboard: window.ecoInitial?.dashboard ?? null,
    cityPresets: window.ecoInitial?.cityPresets ?? [],
    coverageStats: window.ecoInitial?.coverageStats ?? {},
    facilities: [],
    selectedFacility: null,
    facilityFilter: 'all',
    lastCertificate: null,
};

document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });

    if (document.querySelector('#deviceForm')) {
        bindDeviceForm();
    }

    if (document.querySelector('#facilityResults') && document.querySelector('#citySelect')) {
        bindFacilities();
        useCityByName(state.cityPresets[0]?.city ?? 'Delhi NCR', true);
    }

    if (document.querySelector('#pickupForm')) {
        bindPickupPlanner();
    }

    if (document.querySelector('#awarenessModal')) {
        bindModal();
    }

    if (state.dashboard) {
        renderDashboard(state.dashboard);
    }
});

function bindDeviceForm() {
    const form = document.querySelector('#deviceForm');
    const modelInput = form.querySelector('[name="model_name"]');

    document.querySelectorAll('[data-model]').forEach((button) => {
        button.addEventListener('click', () => {
            modelInput.value = button.dataset.model;
            syncPickupModel(button.dataset.model);
            modelInput.focus();
        });
    });

    modelInput.addEventListener('input', () => syncPickupModel(modelInput.value));

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = form.querySelector('button[type="submit"]');
        setBusy(submit, true, 'Analyzing');

        try {
            const response = await fetch('/api/devices/analyze', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body: new FormData(form),
            });
            const payload = await parseJson(response);
            state.analysis = payload.analysis;
            syncPickupModel(payload.analysis.identified_model);
            renderAnalysis(payload.analysis);
            showModal();
            notify('Device impact report ready.');
        } catch (error) {
            notify(error.message || 'Unable to analyze this device.');
        } finally {
            setBusy(submit, false, 'Analyze Device');
        }
    });
}

function bindFacilities() {
    document.querySelector('#findFacilityBtn')?.addEventListener('click', () => loadFacilitiesFromLocation(false));
    document.querySelector('#useCityBtn')?.addEventListener('click', () => {
        useCityByName(document.querySelector('#citySelect')?.value ?? 'Delhi NCR');
    });

    document.querySelectorAll('.city-chip').forEach((button) => {
        button.addEventListener('click', () => {
            const select = document.querySelector('#citySelect');
            if (select) {
                select.value = button.dataset.city;
            }
            fetchFacilities(button.dataset.lat, button.dataset.lng, `${button.dataset.city} selected`);
        });
    });

    document.querySelectorAll('[data-facility-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            state.facilityFilter = button.dataset.facilityFilter;
            document.querySelectorAll('[data-facility-filter]').forEach((filter) => filter.classList.remove('active'));
            button.classList.add('active');
            renderFacilities(state.facilities);
        });
    });
}

function useCityByName(cityName, automatic = false) {
    const preset = state.cityPresets.find((city) => city.city === cityName) ?? state.cityPresets[0];

    if (!preset) {
        return;
    }

    const select = document.querySelector('#citySelect');
    if (select) {
        select.value = preset.city;
    }
    fetchFacilities(preset.lat, preset.lng, automatic ? 'India network loaded' : `${preset.city} selected`);
}

function loadFacilitiesFromLocation(automatic = false) {
    const status = document.querySelector('#locationStatus');
    status.textContent = automatic ? 'Requesting live location...' : 'Finding nearest India e-waste centers...';

    if (!navigator.geolocation) {
        status.textContent = 'Geolocation unavailable. Showing Delhi NCR India network.';
        useCityByName('Delhi NCR');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const { latitude, longitude } = position.coords;

            if (!isInsideIndia(latitude, longitude)) {
                status.textContent = 'Location is outside India. Showing Delhi NCR India network.';
                useCityByName('Delhi NCR');
                return;
            }

            status.textContent = 'Live India location detected. Ranking nearby centers.';
            fetchFacilities(latitude, longitude, 'Live India location detected');
        },
        () => {
            status.textContent = 'Location permission blocked. Showing Delhi NCR India network.';
            useCityByName('Delhi NCR');
        },
        { enableHighAccuracy: true, timeout: 7000, maximumAge: 60000 },
    );
}

function isInsideIndia(lat, lng) {
    return lat >= INDIA_BOUNDS.minLat
        && lat <= INDIA_BOUNDS.maxLat
        && lng >= INDIA_BOUNDS.minLng
        && lng <= INDIA_BOUNDS.maxLng;
}

async function fetchFacilities(lat, lng, label = 'India map updated') {
    try {
        const response = await fetch(`/api/facilities/nearest?lat=${lat}&lng=${lng}&limit=8`, {
            headers: { Accept: 'application/json' },
        });
        const payload = await parseJson(response);
        state.facilities = payload.facilities;
        state.selectedFacility = payload.recommended;
        renderFacilities(payload.facilities);
        document.querySelector('#locationStatus').textContent = `${label}. ${payload.facilities.length} matched centers ranked by distance and service fit.`;
        notify('India facility map updated.');
    } catch (error) {
        notify(error.message || 'Could not load facilities.');
    }
}

function renderFacilities(facilities) {
    const container = document.querySelector('#facilityResults');
    const map = document.querySelector('#facilityMap');

    if (!facilities?.length) {
        container.innerHTML = '<div class="empty-state">No India facilities found for this location.</div>';
        map.src = state.coverageStats.map_embed_url ?? map.src;
        return;
    }

    const visibleFacilities = state.facilityFilter === 'all'
        ? facilities
        : facilities.filter((facility) => Boolean(facility[state.facilityFilter]));

    if (!visibleFacilities.length) {
        container.innerHTML = '<div class="empty-state">No centers match this filter. Try All or another city.</div>';
        return;
    }

    if (!visibleFacilities.some((facility) => facility.id === state.selectedFacility?.id)) {
        state.selectedFacility = visibleFacilities[0];
    }

    map.src = state.selectedFacility?.map_embed_url ?? state.coverageStats.map_embed_url;

    container.innerHTML = visibleFacilities.map((facility) => `
        <article class="facility-card ${state.selectedFacility?.id === facility.id ? 'selected' : ''}" data-facility-id="${facility.id}">
            <div>
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <h3>${escapeHtml(facility.name)}</h3>
                    <span class="status-chip ${facility.open_status.is_open ? 'open' : 'closed'}">${facility.open_status.label}</span>
                </div>
                <p>${escapeHtml(facility.address)}</p>
                <p>${formatter.format(facility.distance_km)} km away - ${facility.travel_time_label} - ${escapeHtml(facility.open_status.detail)}</p>
                <p>${escapeHtml(facility.best_for)}</p>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    ${facility.pickup_available ? '<span class="facility-tag">Pickup</span>' : ''}
                    ${facility.data_wipe ? '<span class="facility-tag">Data wipe</span>' : ''}
                    ${facility.battery_handling ? '<span class="facility-tag">Battery-safe</span>' : ''}
                    ${facility.certificate_supported ? '<span class="facility-tag">QR certificate</span>' : ''}
                    <span class="facility-tag">${escapeHtml(facility.zone)}</span>
                </div>
            </div>
            <div class="flex items-center gap-2 sm:flex-col sm:items-end">
                <span class="reward-pill">${facility.match_score}% fit</span>
                <button class="eco-button eco-button-secondary select-facility" type="button" data-id="${facility.id}">
                    <i data-lucide="check"></i><span>Select</span>
                </button>
                <a class="eco-button eco-button-secondary" href="${facility.directions_url}" target="_blank" rel="noreferrer">
                    <i data-lucide="navigation"></i><span>Directions</span>
                </a>
            </div>
        </article>
    `).join('');

    container.querySelectorAll('.select-facility').forEach((button) => {
        button.addEventListener('click', () => {
            state.selectedFacility = facilities.find((facility) => facility.id === button.dataset.id);
            renderFacilities(facilities);
        });
    });

    createIcons({ icons });
}

function bindPickupPlanner() {
    const form = document.querySelector('#pickupForm');
    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submit = form.querySelector('button[type="submit"]');
        const data = new FormData(form);
        const city = state.cityPresets.find((preset) => preset.city === data.get('city'));

        if (city) {
            data.append('lat', city.lat);
            data.append('lng', city.lng);
        }

        setBusy(submit, true, 'Planning');

        try {
            const response = await fetch('/api/pickups/schedule', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body: data,
            });
            const payload = await parseJson(response);
            renderPickupResult(payload);
            notify(`Pickup plan ${payload.booking_id} created.`);
        } catch (error) {
            notify(error.message || 'Pickup could not be planned.');
        } finally {
            setBusy(submit, false, 'Plan Pickup');
        }
    });
}

function syncPickupModel(model) {
    const input = document.querySelector('#pickupForm [name="model_name"]');
    if (input && model && !input.value) {
        input.value = model;
    }
}

function renderPickupResult(payload) {
    const result = document.querySelector('#pickupResult');
    result.innerHTML = `
        <strong>${escapeHtml(payload.booking_id)} - ${escapeHtml(payload.status)}</strong>
        <p>${escapeHtml(payload.message)}</p>
        <p class="mt-2"><b>${escapeHtml(payload.facility?.name ?? 'Nearest India center')}</b> - ${escapeHtml(payload.city)} - ${payload.points_preview} point preview</p>
        <div class="mt-3 grid gap-2">
            ${payload.prep_checklist.slice(0, 3).map((item) => renderCheckRow(item.step, item.detail)).join('')}
        </div>
    `;
}

function bindModal() {
    document.querySelector('#closeModalBtn')?.addEventListener('click', hideModal);
    document.querySelector('#awarenessModal')?.addEventListener('click', (event) => {
        if (event.target.id === 'awarenessModal') {
            hideModal();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideModal();
        }
    });
    document.querySelector('#quizForm')?.addEventListener('submit', submitQuiz);
    document.querySelector('#completeRecyclingBtn')?.addEventListener('click', completeRecycling);
    document.querySelector('#shareAchievementBtn')?.addEventListener('click', shareAchievement);
}

function renderAnalysis(analysis) {
    document.querySelector('#modalTitle').textContent = `${analysis.identified_model} impact report`;
    document.querySelector('#modalSubtitle').textContent = `${analysis.category_label} - ${analysis.recognition.confidence}% confidence - ${analysis.recommendation.primary_action}`;
    document.querySelector('#ecoScore').textContent = analysis.eco_score;
    document.querySelector('#ecoScoreBar').style.width = `${analysis.eco_score}%`;
    document.querySelector('#didYouKnow').textContent = analysis.did_you_know;

    renderAnalysisStats(analysis);
    renderRecommendation(analysis, document.querySelector('#recommendationBox'));
    renderRecommendation(analysis, document.querySelector('#recommendationPreview'));
    renderChecklist('#complianceChecklist', analysis.india_compliance);
    renderChecklist('#prepChecklist', analysis.prep_checklist.map((item) => ({ label: item.step, detail: item.detail })));

    document.querySelector('#hazardCards').innerHTML = analysis.hazards.map((hazard) => `
        <article class="warning-card">
            <strong>${escapeHtml(hazard.name)} - ${escapeHtml(hazard.severity)}</strong>
            <p>${escapeHtml(hazard.detail)}</p>
        </article>
    `).join('');

    document.querySelector('#environmentEffects').innerHTML = analysis.environmental_effects.map(renderEffect).join('');
    document.querySelector('#healthEffects').innerHTML = analysis.health_effects.map(renderEffect).join('');
    document.querySelector('#ecoTips').innerHTML = analysis.tips.map((tip) => `<li>${escapeHtml(tip)}</li>`).join('');
    renderMaterials(analysis.materials, document.querySelector('#materialRecovery'), analysis.estimated_recycling_value_inr);
    renderQuiz(analysis.quiz);
    createIcons({ icons });
}

function renderAnalysisStats(analysis) {
    const stats = [
        ['Category code', analysis.category_code, 'India e-waste category estimate.'],
        ['Repairability', analysis.repairability, 'Repair, donate, and reuse potential.'],
        ['Value range', `INR ${rupeeFormatter.format(analysis.estimated_recycling_value_inr.min)}-${rupeeFormatter.format(analysis.estimated_recycling_value_inr.max)}`, analysis.estimated_recycling_value_inr.note],
        ['Reward', `${analysis.points} pts`, `Base ${analysis.reward_breakdown.base_points} plus eco score multiplier.`],
    ];

    document.querySelector('#analysisStats').innerHTML = stats.map(([label, value, detail]) => `
        <article class="stat-card">
            <span>${escapeHtml(label)}</span>
            <strong>${escapeHtml(value)}</strong>
            <p>${escapeHtml(detail)}</p>
        </article>
    `).join('');
}

function renderRecommendation(analysis, target) {
    if (!target) {
        return;
    }

    target.innerHTML = `
        <strong>${escapeHtml(titleCase(analysis.recommendation.primary_action))}: ${escapeHtml(analysis.identified_model)}</strong>
        <p>${escapeHtml(analysis.recommendation.rationale)}</p>
        <div class="mt-3 flex flex-wrap gap-2">
            ${analysis.recommendation.alternatives.map((item) => `<span class="facility-tag">${escapeHtml(item)}</span>`).join('')}
        </div>
    `;
}

function renderChecklist(selector, items) {
    document.querySelector(selector).innerHTML = items.map((item) => renderCheckRow(item.label, item.detail)).join('');
}

function renderCheckRow(label, detail) {
    return `
        <div class="check-row">
            <span><i data-lucide="check"></i></span>
            <div>
                <strong>${escapeHtml(label)}</strong>
                <p>${escapeHtml(detail)}</p>
            </div>
        </div>
    `;
}

function renderEffect(effect) {
    return `
        <article class="effect-card">
            <strong>${escapeHtml(effect.label)}</strong>
            <p>${escapeHtml(effect.detail)}</p>
        </article>
    `;
}

function renderMaterials(materials, target, valueEstimate = null) {
    if (!target) {
        return;
    }

    const valueCard = valueEstimate ? `
        <article class="material-card">
            <span>Indicative value</span>
            <strong>INR ${rupeeFormatter.format(valueEstimate.min)}-${rupeeFormatter.format(valueEstimate.max)}</strong>
            <p>${escapeHtml(valueEstimate.note)}</p>
        </article>
    ` : '';

    target.innerHTML = valueCard + materials.map((material) => `
        <article class="material-card">
            <span>${escapeHtml(material.name)}</span>
            <strong>${formatter.format(material.amount)}${escapeHtml(material.unit)}</strong>
            <p>${escapeHtml(material.use ?? 'Recoverable material stream')}</p>
        </article>
    `).join('');
}

function renderQuiz(quiz) {
    document.querySelector('#quizQuestion').textContent = quiz.question;
    document.querySelector('#quizResult').textContent = '';
    document.querySelector('#quizOptions').innerHTML = quiz.options.map((option, index) => `
        <label class="quiz-option">
            <input type="radio" name="answer" value="${escapeHtml(option.value)}" ${index === 0 ? 'required' : ''}>
            <span>${escapeHtml(option.label)}</span>
        </label>
    `).join('');
}

async function submitQuiz(event) {
    event.preventDefault();
    const selected = new FormData(event.currentTarget).get('answer');

    try {
        const response = await fetch('/api/quiz/submit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({ answer: selected }),
        });
        const payload = await parseJson(response);
        const result = document.querySelector('#quizResult');
        result.textContent = `${payload.message} ${payload.points_preview ? `+${payload.points_preview} bonus points preview.` : ''}`;
        result.className = `mt-3 text-sm font-medium ${payload.correct ? 'text-emerald-700' : 'text-rose-700'}`;
    } catch (error) {
        notify(error.message || 'Quiz could not be submitted.');
    }
}

async function completeRecycling() {
    if (!state.analysis) {
        notify('Analyze a device first.');
        return;
    }

    const button = document.querySelector('#completeRecyclingBtn');
    setBusy(button, true, 'Recording');

    try {
        const response = await fetch('/api/recycling/complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                model_name: state.analysis.identified_model,
                condition: state.analysis.condition,
                facility_id: state.selectedFacility?.id,
                holder_name: 'Eco Recycler',
            }),
        });
        const payload = await parseJson(response);
        state.lastCertificate = payload.certificate;
        renderCertificate(payload);
        await refreshDashboard();
        notify(`Recorded. ${payload.wallet.points_added} eco points added.`);
    } catch (error) {
        notify(error.message || 'Could not record disposal.');
    } finally {
        setBusy(button, false, 'Record Disposal');
    }
}

function renderCertificate(payload) {
    const panel = document.querySelector('#certificatePanel');
    if (!panel) {
        return;
    }

    panel.innerHTML = `
        <strong>${escapeHtml(payload.certificate.number)}</strong>
        <p class="mt-2">${escapeHtml(payload.certificate.share_text)}</p>
        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
            <a class="eco-button eco-button-primary justify-center" href="${payload.certificate.download_url}">
                <i data-lucide="download"></i><span>Download PDF</span>
            </a>
            <a class="eco-button eco-button-secondary justify-center" href="${payload.certificate.verify_url}" target="_blank" rel="noreferrer">
                <i data-lucide="qr-code"></i><span>Verify QR</span>
            </a>
        </div>
    `;
    createIcons({ icons });
}

async function refreshDashboard() {
    const response = await fetch('/api/dashboard', { headers: { Accept: 'application/json' } });
    state.dashboard = await parseJson(response);
    renderDashboard(state.dashboard);
}

function renderDashboard(dashboard) {
    if (!dashboard) {
        return;
    }

    setDashboardText('totals.devices', dashboard.totals.devices);
    setDashboardText('totals.ewaste_kg', dashboard.totals.ewaste_kg);
    setDashboardText('totals.pollution_prevented_kg', dashboard.totals.pollution_prevented_kg);
    setDashboardText('totals.co2_kg', dashboard.totals.co2_kg);
    setDashboardText('user.points', dashboard.user.points);
    setDashboardText('user.streak_days', dashboard.user.streak_days);
    setDashboardText('user.co2_kg', dashboard.user.co2_kg);
    setDashboardText('user.devices', dashboard.user.devices);

    renderIndiaImpact(dashboard.india_impact);
    renderMonthly(dashboard.monthly);
    renderMaterials(dashboard.materials, document.querySelector('#materialTotals'));
    renderBadges(dashboard.user.badges);
    renderCoupons(dashboard.coupons);
    renderChallenges(dashboard.challenges);
    renderLeaderboard(dashboard.leaderboard);
    renderStateRanking(dashboard.state_ranking);
}

function setDashboardText(key, value) {
    document.querySelectorAll(`[data-dashboard="${key}"]`).forEach((element) => {
        element.textContent = formatter.format(value);
    });
}

function renderIndiaImpact(items = []) {
    const target = document.querySelector('#indiaImpact');
    if (!target) {
        return;
    }

    target.innerHTML = items.map((item) => `
        <article class="impact-card">
            <span>${escapeHtml(item.label)}</span>
            <strong>${formatter.format(item.value)} ${escapeHtml(item.unit)}</strong>
            <p>${escapeHtml(item.detail)}</p>
        </article>
    `).join('');
}

function renderMonthly(months) {
    const graph = document.querySelector('#monthlyGraph');
    if (!graph || !months) {
        return;
    }

    const max = Math.max(...months.map((month) => month.co2_kg), 1);
    graph.innerHTML = months.map((month) => {
        const height = Math.max(8, Math.round((month.co2_kg / max) * 150));
        return `
            <div class="month-bar">
                <span style="height:${height}px"></span>
                <strong>${escapeHtml(month.label)}</strong>
                <small>${formatter.format(month.co2_kg)}kg</small>
            </div>
        `;
    }).join('');
}

function renderBadges(badges) {
    const target = document.querySelector('#badges');
    if (!target || !badges) {
        return;
    }

    target.innerHTML = badges.map((badge) => `
        <div class="badge-row ${badge.earned ? 'earned' : ''}">
            <span class="text-sm font-semibold">${escapeHtml(badge.name)}</span>
            <span class="status-chip ${badge.earned ? 'open' : ''}">${badge.earned ? 'Earned' : `${badge.threshold} pts`}</span>
        </div>
    `).join('');
}

function renderCoupons(coupons) {
    const target = document.querySelector('#coupons');
    if (!target || !coupons) {
        return;
    }

    target.innerHTML = coupons.map((coupon) => `
        <div class="coupon-row">
            <span class="text-sm text-zinc-700">${escapeHtml(coupon.title)}</span>
            <span class="status-chip ${coupon.available ? 'open' : ''}">${coupon.cost} pts</span>
        </div>
    `).join('');
}

function renderChallenges(challenges) {
    const target = document.querySelector('#challenges');
    if (!target || !challenges) {
        return;
    }

    target.innerHTML = challenges.map((challenge) => `
        <div class="challenge-row">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <strong class="text-sm text-zinc-900">${escapeHtml(challenge.title)}</strong>
                    <p class="mt-1 text-xs text-zinc-500">${challenge.points} points - ${escapeHtml(challenge.status)}</p>
                </div>
                <i data-lucide="${challenge.status === 'completed' ? 'circle-check' : 'flame'}" class="text-emerald-700"></i>
            </div>
            <div class="challenge-progress"><span style="width:${challenge.progress}%"></span></div>
        </div>
    `).join('');
    createIcons({ icons });
}

function renderLeaderboard(rows) {
    const target = document.querySelector('#leaderboard');
    if (!target || !rows) {
        return;
    }

    target.innerHTML = rows.map((row, index) => `
        <div class="leader-row">
            <div>
                <strong class="text-sm text-zinc-900">#${index + 1} ${escapeHtml(row.name)}</strong>
                <p class="mt-1 text-xs text-zinc-500">${row.devices} recycled devices</p>
            </div>
            <span class="status-chip open">${formatter.format(row.points)} pts</span>
        </div>
    `).join('');
}

function renderStateRanking(rows = []) {
    const target = document.querySelector('#stateRanking');
    if (!target) {
        return;
    }

    target.innerHTML = rows.map((row, index) => `
        <div class="leader-row">
            <div>
                <strong class="text-sm text-zinc-900">#${index + 1} ${escapeHtml(row.state)}</strong>
                <p class="mt-1 text-xs text-zinc-500">${row.devices} community actions</p>
            </div>
            <span class="status-chip open">${formatter.format(row.points)} pts</span>
        </div>
    `).join('');
}

async function shareAchievement() {
    const text = state.lastCertificate?.share_text
        ?? (state.analysis ? `I checked the India recycling impact of ${state.analysis.identified_model} with EcoCycle Smart.` : 'I am using EcoCycle Smart India to recycle electronics safely.');
    const url = state.lastCertificate?.verify_url ?? window.location.href;

    if (navigator.share) {
        await navigator.share({ title: 'EcoCycle Smart India', text, url });
        return;
    }

    await navigator.clipboard?.writeText(`${text} ${url}`);
    notify('Achievement copied.');
}

function showModal() {
    const modal = document.querySelector('#awarenessModal');
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    document.querySelector('#closeModalBtn')?.focus();
}

function hideModal() {
    const modal = document.querySelector('#awarenessModal');
    if (!modal || modal.hidden) {
        return;
    }
    modal.hidden = true;
    document.body.style.overflow = '';
}

async function parseJson(response) {
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(firstError || data.message || 'Request failed.');
    }

    return data;
}

function setBusy(button, busy, label) {
    if (!button) {
        return;
    }

    button.disabled = busy;
    const span = button.querySelector('span');
    if (span) {
        span.textContent = label;
    }
}

function notify(message) {
    const toast = document.querySelector('#toast');
    if (!toast) {
        return;
    }

    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(notify.timer);
    notify.timer = setTimeout(() => toast.classList.remove('show'), 3200);
}

function titleCase(value) {
    return String(value)
        .split(' ')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
