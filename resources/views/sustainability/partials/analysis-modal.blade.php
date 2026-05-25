<div id="awarenessModal" class="modal-backdrop" hidden role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <section class="modal-panel">
        <header class="modal-top">
            <div>
                <span class="eyebrow">Smart analysis</span>
                <h2 id="modalTitle" class="mt-1 text-2xl font-black">Device impact report</h2>
                <p id="modalSubtitle" class="mt-1 text-sm" style="color:var(--app-muted);">Analyze a device to see hazards, rewards, and recycling guidance.</p>
            </div>
            <button id="closeModalBtn" class="icon-button" type="button" aria-label="Close analysis">
                <i data-lucide="x"></i>
            </button>
        </header>

        <div class="modal-grid">
            <article class="score-card">
                <span>Eco score</span>
                <strong id="ecoScore">0</strong>
                <div class="score-meter"><span id="ecoScoreBar" style="width:0%"></span></div>
                <div id="recommendationPreview" class="recommendation-box mt-4">
                    <strong>Recommendation will appear here</strong>
                </div>
            </article>

            <article class="surface-muted p-4">
                <span class="subhead">Reward preview</span>
                <div id="analysisStats" class="mt-3 grid gap-3 sm:grid-cols-2">
                    <article class="stat-card"><span>Category</span><strong>-</strong><p>E-waste group</p></article>
                    <article class="stat-card"><span>Repair</span><strong>-</strong><p>Reuse potential</p></article>
                    <article class="stat-card"><span>Value</span><strong>-</strong><p>Estimate</p></article>
                    <article class="stat-card"><span>Reward</span><strong>0 pts</strong><p>Points preview</p></article>
                </div>
            </article>

            <article class="surface-muted p-4 sm:col-span-2">
                <span class="subhead">Environmental impact</span>
                <div class="mt-3 grid gap-3 sm:grid-cols-4">
                    <div class="mini-stat"><span>E-waste</span><strong data-modal="ewaste_kg">-</strong></div>
                    <div class="mini-stat"><span>CO2 saved</span><strong data-modal="co2_kg">-</strong></div>
                    <div class="mini-stat"><span>Water safe</span><strong data-modal="water_liters">-</strong></div>
                    <div class="mini-stat"><span>Landfill saved</span><strong data-modal="landfill_liters">-</strong></div>
                </div>
            </article>

            <article class="surface-muted p-4">
                <span class="subhead">Materials</span>
                <p class="mt-1 text-sm" style="color:var(--app-muted);">Recoverable materials from this device.</p>
                <div id="materialRecovery" class="mt-3 grid gap-3"></div>
            </article>

            <article class="surface-muted p-4">
                <span class="subhead">Hazards</span>
                <p class="mt-1 text-sm" style="color:var(--app-muted);">Toxic components that need safe handling.</p>
                <div id="hazardCards" class="mt-3 grid gap-3"></div>
            </article>

            <article class="surface-muted p-4">
                <span class="subhead">Action plan</span>
                <div id="recommendationBox" class="recommendation-box mt-3"></div>
                <div id="prepChecklist" class="mt-3 grid gap-2"></div>
                <ul id="ecoTips" class="mt-3 grid gap-2 text-sm" style="color:var(--app-muted);"></ul>
            </article>

            <article class="surface-muted p-4">
                <span class="subhead">India guide</span>
                <p class="mt-1 text-sm" style="color:var(--app-muted);">E-Waste Management Rules guidance for this device.</p>
                <div id="complianceChecklist" class="mt-3 grid gap-2"></div>
            </article>

            <article class="surface-muted p-4 sm:col-span-2">
                <span class="subhead">Awareness</span>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <div id="environmentEffects" class="grid gap-3"></div>
                    <div id="healthEffects" class="grid gap-3"></div>
                </div>
                <div class="did-you-know mt-3">
                    <span><i data-lucide="lightbulb"></i></span>
                    <p id="didYouKnow">Analyze a device to unlock a short awareness insight.</p>
                </div>
            </article>

            <article class="quiz-card sm:col-span-2">
                <strong id="quizQuestion">Quick check</strong>
                <form id="quizForm" class="mt-3 grid gap-2">
                    <div id="quizOptions" class="grid gap-2"></div>
                    <button class="eco-button eco-button-secondary justify-center" type="submit">
                        <i data-lucide="circle-check"></i>
                        <span>Submit Answer</span>
                    </button>
                    <p id="quizResult" class="mt-2 text-sm"></p>
                </form>
            </article>

            <article id="certificatePanel" class="certificate-panel sm:col-span-2">
                <strong>Ready to record disposal?</strong>
                <p class="mt-1 text-sm" style="color:var(--app-muted);">After safe handover, record disposal to add points and generate a certificate.</p>
            </article>
        </div>

        <footer class="modal-actions">
            <button id="completeRecyclingBtn" class="eco-button eco-button-primary" type="button">
                <i data-lucide="badge-check"></i>
                <span>Record Disposal</span>
            </button>
            <button id="shareAchievementBtn" class="eco-button eco-button-secondary" type="button">
                <i data-lucide="share-2"></i>
                <span>Share</span>
            </button>
        </footer>
    </section>
</div>
