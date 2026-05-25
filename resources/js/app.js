import './bootstrap';
import {
    ArrowRight,
    BadgeCheck,
    Bell,
    BookOpen,
    BrainCircuit,
    Building2,
    CalendarCheck,
    Check,
    CheckCircle,
    ChevronRight,
    CircleCheck,
    ClipboardCheck,
    Clock,
    Download,
    Eye,
    FileBadge,
    Flame,
    Gift,
    HeartPulse,
    Home,
    ImagePlus,
    LayoutDashboard,
    Lightbulb,
    LocateFixed,
    Lock,
    LockKeyhole,
    LogIn,
    LogOut,
    Mail,
    MailCheck,
    Map,
    MapPin,
    MapPinned,
    Menu,
    MessageCircle,
    Navigation,
    PackagePlus,
    Phone,
    PlayCircle,
    Plus,
    QrCode,
    Recycle,
    RefreshCw,
    RotateCw,
    Save,
    ScanLine,
    Search,
    Send,
    Settings,
    Share2,
    Shield,
    ShieldAlert,
    ShieldCheck,
    SlidersHorizontal,
    Sparkles,
    Store,
    SunMoon,
    TriangleAlert,
    Trophy,
    Truck,
    UserPlus,
    UserRound,
    Users,
    Waves,
    X,
    createIcons,
} from 'lucide';

const icons = {
    ArrowRight,
    BadgeCheck,
    Bell,
    BookOpen,
    BrainCircuit,
    Building2,
    CalendarCheck,
    Check,
    CheckCircle,
    ChevronRight,
    CircleCheck,
    ClipboardCheck,
    Clock,
    Download,
    Eye,
    FileBadge,
    Flame,
    Gift,
    HeartPulse,
    Home,
    ImagePlus,
    LayoutDashboard,
    Lightbulb,
    LocateFixed,
    Lock,
    LockKeyhole,
    LogIn,
    LogOut,
    Mail,
    MailCheck,
    Map,
    MapPin,
    MapPinned,
    Menu,
    MessageCircle,
    Navigation,
    PackagePlus,
    Phone,
    PlayCircle,
    Plus,
    QrCode,
    Recycle,
    RefreshCw,
    RotateCw,
    Save,
    ScanLine,
    Search,
    Send,
    Settings,
    Share2,
    Shield,
    ShieldAlert,
    ShieldCheck,
    SlidersHorizontal,
    Sparkles,
    Store,
    SunMoon,
    TriangleAlert,
    Trophy,
    Truck,
    UserPlus,
    UserRound,
    Users,
    Waves,
    X,
};

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
    bindSideNav();
    bindThemeControls();
    bindPointerFx();
    bindPasswordToggles();
    bindGlobalSearch();
    bindOtpInput();
    bindClientValidation();
    bindSmoothAnchors();

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

window.addEventListener('error', () => notify('Something went wrong. Please try again.'));
window.addEventListener('unhandledrejection', () => notify('A network request failed.'));

function bindDeviceForm() {
    const form = document.querySelector('#deviceForm');
    const modelInput = form.querySelector('[name="model_name"]');
    const imageInput = form.querySelector('#deviceImageInput');
    const imageLabel = form.querySelector('#deviceImageLabel');

    document.querySelectorAll('[data-model]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-model]').forEach((sample) => sample.classList.remove('active'));
            button.classList.add('active');
            modelInput.value = button.dataset.model;
            syncPickupModel(button.dataset.model);
            modelInput.focus();
        });
    });

    modelInput.addEventListener('input', () => syncPickupModel(modelInput.value));
    imageInput?.addEventListener('change', () => {
        imageLabel.textContent = imageInput.files?.[0]?.name ?? 'Upload photo';
    });

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
            notify('Report ready.');
        } catch (error) {
            notify(error.message || 'Unable to analyze.');
        } finally {
            setBusy(submit, false, 'Analyze');
        }
    });
}

function bindFacilities() {
    document.querySelector('#findFacilityBtn')?.addEventListener('click', (event) => loadFacilitiesFromLocation(false, event.currentTarget));
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

function loadFacilitiesFromLocation(automatic = false, button = null) {
    const status = document.querySelector('#locationStatus');
    if (status) {
        status.textContent = automatic ? 'Requesting location...' : 'Finding centers...';
    }
    setBusy(button, true, 'Finding');

    if (!navigator.geolocation) {
        if (status) {
            status.textContent = 'Showing Delhi NCR.';
        }
        setBusy(button, false, 'Live');
        useCityByName('Delhi NCR');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const { latitude, longitude } = position.coords;

            if (!isInsideIndia(latitude, longitude)) {
                if (status) {
                    status.textContent = 'Showing Delhi NCR.';
                }
                setBusy(button, false, 'Live');
                useCityByName('Delhi NCR');
                return;
            }

            if (status) {
                status.textContent = 'Live location found.';
            }
            setBusy(button, false, 'Live');
            fetchFacilities(latitude, longitude, 'Live India location detected');
        },
        () => {
            if (status) {
                status.textContent = 'Showing Delhi NCR.';
            }
            setBusy(button, false, 'Live');
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
    renderFacilitySkeleton();

    try {
        const response = await fetch(`/api/facilities/nearest?lat=${lat}&lng=${lng}&limit=8`, {
            headers: { Accept: 'application/json' },
        });
        const payload = await parseJson(response);
        state.facilities = payload.facilities;
        state.selectedFacility = payload.recommended;
        renderFacilities(payload.facilities);
        const status = document.querySelector('#locationStatus');
        if (status) {
            status.textContent = `${label}. ${payload.facilities.length} centers found.`;
        }
        notify('Map updated.');
    } catch (error) {
        notify(error.message || 'Could not load facilities.');
    }
}

function renderFacilitySkeleton() {
    const container = document.querySelector('#facilityResults');

    if (!container) {
        return;
    }

    container.innerHTML = Array.from({ length: 4 }).map(() => `
        <article class="facility-card skeleton-card">
            <div>
                <span class="skeleton-line w-2/3"></span>
                <span class="skeleton-line w-full"></span>
                <span class="skeleton-line w-1/2"></span>
            </div>
            <div class="grid gap-2">
                <span class="skeleton-pill"></span>
                <span class="skeleton-pill"></span>
            </div>
        </article>
    `).join('');
}

function renderFacilities(facilities) {
    const container = document.querySelector('#facilityResults');
    const map = document.querySelector('#facilityMap');

    if (!container || !map) {
        return;
    }

    if (!facilities?.length) {
        container.innerHTML = '<div class="empty-state">No centers found. Try another city or use live location.</div>';
        map.src = state.coverageStats.map_embed_url ?? map.src;
        return;
    }

    const visibleFacilities = state.facilityFilter === 'all'
        ? facilities
        : facilities.filter((facility) => Boolean(facility[state.facilityFilter]));

    if (!visibleFacilities.length) {
        container.innerHTML = '<div class="empty-state">No centers match that filter. Try All or choose another service.</div>';
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
                <p><b>${formatter.format(facility.distance_km)} km</b> - ${escapeHtml(facility.travel_time_label)} - ${escapeHtml(facility.city)}</p>
                <div class="facility-services">
                    ${facility.services.slice(0, 3).map((service) => `<span class="facility-tag">${escapeHtml(service)}</span>`).join('')}
                </div>
            </div>
            <div class="facility-actions">
                <button class="eco-button eco-button-secondary select-facility" type="button" data-id="${facility.id}">
                    <i data-lucide="${state.selectedFacility?.id === facility.id ? 'circle-check' : 'map'}"></i><span>Map</span>
                </button>
                <a class="eco-button eco-button-secondary" href="${escapeHtml(facility.directions_url)}" target="_blank" rel="noreferrer">
                    <i data-lucide="navigation"></i><span>Go</span>
                </a>
                <a class="eco-button eco-button-primary" href="/pickup?city=${encodeURIComponent(facility.city)}">
                    <i data-lucide="truck"></i><span>Pickup</span>
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
    const params = new URLSearchParams(window.location.search);
    const city = params.get('city');
    const device = params.get('device');

    if (city) {
        const cityInput = form?.querySelector('[name="city"]');
        if (cityInput && [...cityInput.options].some((option) => option.value === city)) {
            cityInput.value = city;
        }
    }

    if (device) {
        const deviceInput = form?.querySelector('[name="model_name"]');
        if (deviceInput) {
            deviceInput.value = device;
        }
    }

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
            notify('Pickup plan ready.');
        } catch (error) {
            notify(error.message || 'Pickup could not be planned.');
        } finally {
            setBusy(submit, false, 'Confirm Pickup');
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
    if (!result) {
        return;
    }

    const directions = payload.facility?.directions_url
        ? `<a class="eco-button eco-button-secondary mt-3" href="${escapeHtml(payload.facility.directions_url)}" target="_blank" rel="noreferrer"><i data-lucide="navigation"></i><span>Directions</span></a>`
        : '';

    result.innerHTML = `
        <strong>${escapeHtml(payload.booking_id)} - ${escapeHtml(payload.status)}</strong>
        <p>${escapeHtml(payload.message)}</p>
        <p class="mt-2"><b>${escapeHtml(payload.facility?.name ?? 'Nearest center')}</b> - ${escapeHtml(payload.city)}</p>
        <p class="mt-1">${escapeHtml(payload.preferred_window)} - ${payload.points_preview} pts</p>
        ${directions}
        <div class="mt-3 grid gap-2">
            ${payload.prep_checklist.slice(0, 3).map((item) => renderCheckRow(item.step, item.detail)).join('')}
        </div>
    `;
    createIcons({ icons });
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
    document.querySelector('#modalSubtitle').textContent = `${analysis.category_label} - ${analysis.recommendation.primary_action}`;
    document.querySelector('#ecoScore').textContent = analysis.eco_score;
    document.querySelector('#ecoScoreBar').style.width = `${analysis.eco_score}%`;
    document.querySelector('#didYouKnow').textContent = analysis.did_you_know;

    renderAnalysisStats(analysis);
    renderModalImpact(analysis);
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
        ['Category', analysis.category_code, 'E-waste group'],
        ['Repair', analysis.repairability, 'Reuse potential'],
        ['Value', `INR ${rupeeFormatter.format(analysis.estimated_recycling_value_inr.min)}-${rupeeFormatter.format(analysis.estimated_recycling_value_inr.max)}`, 'Estimate'],
        ['Reward', `${analysis.points} pts`, 'Points preview'],
    ];

    document.querySelector('#analysisStats').innerHTML = stats.map(([label, value, detail]) => `
        <article class="stat-card">
            <span>${escapeHtml(label)}</span>
            <strong>${escapeHtml(value)}</strong>
            <p>${escapeHtml(detail)}</p>
        </article>
    `).join('');
}

function renderModalImpact(analysis) {
    const impact = analysis.impact ?? analysis.environmental_impact ?? {};
    const ewasteKg = analysis.ewaste_kg ?? impact.ewaste_kg ?? analysis.estimated_weight_kg ?? null;
    const co2Kg = analysis.co2_kg ?? impact.co2_kg ?? impact.co2_saved_kg ?? (ewasteKg ? ewasteKg * 2.4 : null);
    const waterLiters = impact.water_liters ?? impact.water_saved_liters ?? (ewasteKg ? ewasteKg * 740 : null);
    const landfillLiters = impact.landfill_liters ?? impact.landfill_saved_liters ?? (ewasteKg ? ewasteKg * 4.5 : null);

    setModalMetric('ewaste_kg', ewasteKg, 'kg');
    setModalMetric('co2_kg', co2Kg, 'kg');
    setModalMetric('water_liters', waterLiters, 'L');
    setModalMetric('landfill_liters', landfillLiters, 'L');
}

function setModalMetric(key, value, unit) {
    document.querySelectorAll(`[data-modal="${key}"]`).forEach((element) => {
        const numeric = Number(value);
        element.textContent = Number.isFinite(numeric) ? `${formatter.format(numeric)} ${unit}` : '-';
    });
}

function renderRecommendation(analysis, target) {
    if (!target) {
        return;
    }

    target.innerHTML = `
        <strong>${escapeHtml(titleCase(analysis.recommendation.primary_action))}: ${escapeHtml(analysis.identified_model)}</strong>
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
    renderRecentActivity(dashboard.recent_activity);
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
            <span class="text-sm font-bold">${escapeHtml(coupon.title)}</span>
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
                    <strong class="text-sm">${escapeHtml(challenge.title)}</strong>
                <p class="mt-1 text-xs">${challenge.points} pts - ${escapeHtml(challenge.status)}</p>
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
                <strong class="text-sm">#${index + 1} ${escapeHtml(row.name)}</strong>
                <p class="mt-1 text-xs">${row.devices} devices</p>
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
                <strong class="text-sm">#${index + 1} ${escapeHtml(row.state)}</strong>
                <p class="mt-1 text-xs">${row.devices} actions</p>
            </div>
            <span class="status-chip open">${formatter.format(row.points)} pts</span>
        </div>
    `).join('');
}

function renderRecentActivity(rows = []) {
    const target = document.querySelector('#recentActivity');
    if (!target) {
        return;
    }

    if (!rows.length) {
        target.innerHTML = `
            <div class="empty-state">
                No activity yet. Scan a device and record disposal to build your verified history.
            </div>
        `;
        return;
    }

    target.innerHTML = rows.map((row) => `
        <div class="activity-row">
            <div>
                <strong class="text-sm">${escapeHtml(row.device)}</strong>
                <p class="mt-1 text-xs">${escapeHtml(row.category)} - ${escapeHtml(row.impact)}</p>
            </div>
            <div class="text-right">
                <span class="status-chip open">${formatter.format(row.points)} pts</span>
                <p class="mt-1 text-[11px]">${escapeHtml(row.date)}</p>
            </div>
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
        if (busy && !button.dataset.defaultLabel) {
            button.dataset.defaultLabel = span.textContent;
        }
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

function bindSmoothAnchors() {
    document.querySelectorAll('a[href^="#"]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const target = document.querySelector(link.getAttribute('href'));
            if (!target) {
                return;
            }

            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}

function bindThemeControls() {
    const applyTheme = (theme) => {
        const resolved = theme === 'system'
            ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : theme;
        document.documentElement.dataset.theme = resolved;
        localStorage.setItem('ecocycle-theme', theme);
    };

    document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
        const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        notify(`${titleCase(next)} mode enabled.`);
    });

    document.querySelector('[data-theme-select]')?.addEventListener('change', (event) => {
        applyTheme(event.target.value);
    });
}

function bindPointerFx() {
    if (matchMedia('(pointer: coarse), (prefers-reduced-motion: reduce)').matches) {
        return;
    }

    let frame = null;
    document.querySelectorAll('.eco-button, .quick-card, .metric-panel, .timeline-step, .facility-card').forEach((element) => {
        element.addEventListener('pointermove', (event) => {
            if (frame) {
                return;
            }

            frame = requestAnimationFrame(() => {
                const rect = element.getBoundingClientRect();
                element.style.setProperty('--x', `${event.clientX - rect.left}px`);
                element.style.setProperty('--y', `${event.clientY - rect.top}px`);
                frame = null;
            });
        });
    });
}

function bindSideNav() {
    const backdrop = document.querySelector('.side-nav-backdrop');
    const desktopQuery = window.matchMedia('(min-width: 1024px)');

    const isDesktop = () => desktopQuery.matches;

    const hideBackdrop = () => {
        if (backdrop) {
            backdrop.hidden = true;
        }
    };

    const open = () => {
        document.body.classList.remove('sidebar-collapsed');
        document.body.classList.add('sidebar-open');
        if (backdrop && !isDesktop()) {
            backdrop.hidden = false;
        }
    };

    const close = (collapseDesktop = false) => {
        document.body.classList.remove('sidebar-open');

        if (isDesktop() && collapseDesktop) {
            document.body.classList.add('sidebar-collapsed');
        }

        hideBackdrop();
    };

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => button.addEventListener('click', open));
    document.querySelectorAll('[data-sidebar-close]').forEach((button) => button.addEventListener('click', () => close(true)));
    document.querySelectorAll('.side-nav a').forEach((link) => link.addEventListener('click', () => {
        if (!isDesktop()) {
            close(false);
        }
    }));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close(true);
        }
    });
    desktopQuery.addEventListener('change', hideBackdrop);
}

function bindPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.closest('.password-field')?.querySelector('input');

            if (!input) {
                return;
            }

            input.type = input.type === 'password' ? 'text' : 'password';
            button.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
        });
    });
}

function bindOtpInput() {
    document.querySelector('[data-otp-input]')?.addEventListener('input', (event) => {
        event.target.value = event.target.value.replace(/\D/g, '').slice(0, 6);
    });
}

function bindClientValidation() {
    document.querySelectorAll('[data-validate-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.submitter?.formNoValidate) {
                return;
            }

            if (!form.checkValidity()) {
                event.preventDefault();
                notify('Check the highlighted fields.');
                form.classList.add('was-validated');
            }
        });
    });
}

function bindGlobalSearch() {
    const dialog = document.querySelector('#globalSearch');
    const input = document.querySelector('#globalSearchInput');
    const results = document.querySelector('#globalSearchResults');
    let timer = null;

    if (!dialog || !input || !results) {
        return;
    }

    const open = () => {
        dialog.hidden = false;
        document.body.style.overflow = 'hidden';
        input.focus();
    };

    const close = () => {
        dialog.hidden = true;
        document.body.style.overflow = '';
        input.value = '';
        results.innerHTML = '<div class="empty-state">Start typing to search across the workspace.</div>';
    };

    document.querySelectorAll('[data-search-open]').forEach((button) => button.addEventListener('click', open));
    document.querySelectorAll('[data-search-close]').forEach((button) => button.addEventListener('click', close));
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            close();
        }
    });
    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            open();
        }
        if (event.key === 'Escape' && !dialog.hidden) {
            close();
        }
    });

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const query = input.value.trim();

        if (query.length < 2) {
            results.innerHTML = '<div class="empty-state">Type at least two characters.</div>';
            return;
        }

        results.innerHTML = '<div class="search-loading"><span></span><span></span><span></span></div>';
        timer = setTimeout(async () => {
            try {
                const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`, {
                    headers: { Accept: 'application/json' },
                });
                const payload = await parseJson(response);
                renderSearchResults(payload.data ?? []);
            } catch (error) {
                results.innerHTML = `<div class="empty-state">${escapeHtml(error.message || 'Search failed.')}</div>`;
            }
        }, 180);
    });
}

function renderSearchResults(items) {
    const results = document.querySelector('#globalSearchResults');

    if (!results) {
        return;
    }

    if (!items.length) {
        results.innerHTML = '<div class="empty-state">No matching results.</div>';
        return;
    }

    results.innerHTML = items.map((item) => `
        <a class="search-result-row" href="${escapeHtml(item.url)}">
            <span><i data-lucide="${escapeHtml(item.icon)}"></i></span>
            <div>
                <strong>${escapeHtml(item.title)}</strong>
                <small>${escapeHtml(item.subtitle)}</small>
            </div>
            <em>${escapeHtml(item.type)}</em>
        </a>
    `).join('');
    createIcons({ icons });
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
