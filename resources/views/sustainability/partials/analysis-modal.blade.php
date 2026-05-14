<div id="awarenessModal" class="modal-backdrop" hidden>
    <section class="modal-panel compact-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-top">
            <div>
                <span class="eyebrow">Device report</span>
                <h2 id="modalTitle">Impact report</h2>
                <p id="modalSubtitle"></p>
            </div>
            <button id="closeModalBtn" class="icon-button" type="button" aria-label="Close report">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="modal-grid">
            <aside class="score-card">
                <span>Eco score</span>
                <strong id="ecoScore">0</strong>
                <div class="score-meter"><span id="ecoScoreBar"></span></div>
            </aside>

            <div id="analysisStats" class="grid gap-3 sm:grid-cols-2"></div>
            <div id="recommendationBox" class="recommendation-box"></div>

            <div>
                <h3 class="subhead">Safety alerts</h3>
                <div id="hazardCards" class="mt-3 grid gap-3 sm:grid-cols-2"></div>
            </div>

            <div>
                <h3 class="subhead">Next steps</h3>
                <div id="prepChecklist" class="mt-3 grid gap-2"></div>
            </div>

            <div>
                <h3 class="subhead">India checklist</h3>
                <div id="complianceChecklist" class="mt-3 grid gap-2"></div>
            </div>

            <form id="quizForm" class="quiz-card">
                <h3 class="subhead">Quick quiz</h3>
                <p id="quizQuestion" class="mt-2 text-sm text-zinc-600"></p>
                <div id="quizOptions" class="mt-3 grid gap-2"></div>
                <button class="eco-button eco-button-secondary mt-3" type="submit">Check</button>
                <p id="quizResult" class="mt-3 text-sm font-medium" aria-live="polite"></p>
            </form>

            <div class="hidden">
                <div id="environmentEffects"></div>
                <div id="healthEffects"></div>
                <ul id="ecoTips"></ul>
                <p id="didYouKnow"></p>
            </div>
        </div>

        <div class="modal-actions">
            <button id="completeRecyclingBtn" class="eco-button eco-button-primary justify-center" type="button">
                <i data-lucide="file-badge"></i>
                <span>Record Disposal</span>
            </button>
            <button id="shareAchievementBtn" class="eco-button eco-button-secondary justify-center" type="button">
                <i data-lucide="share-2"></i>
                <span>Share</span>
            </button>
        </div>
    </section>
</div>
