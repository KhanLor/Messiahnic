<?php
require __DIR__ . '/bootstrap.php';
ensure_daily_ai_prayers();

$pageTitle = 'Prayer Requests';

$manilaNow = philippines_now();
$manilaTime = $manilaNow->format('g:i A');
$activeSlot = prayer_slot_for_hour((int) $manilaNow->format('G'));
$slotPrayers = [
    'Morning' => null,
    'Afternoon' => null,
    'Evening' => null,
];

try {
    $slotPrayers = daily_admin_prayers_by_slot();
} catch (Throwable $exception) {
    flash('error', 'Prayer requests could not be loaded yet.');
}

$currentPrayer = $slotPrayers[$activeSlot] ?? null;

include __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="section-title">
        <h2>Daily prayer system</h2>
        <span class="muted">Philippine time: <?= e($manilaTime) ?></span>
    </div>

    <p class="muted">At 8:00 AM (Philippine time), the Morning Prayer appears. Afternoon and Evening prayers follow by time slot.</p>

    <div class="actions prayer-slot-tabs">
        <button class="btn btn-ghost prayer-slot-btn" type="button" data-slot="Morning">Morning</button>
        <button class="btn btn-ghost prayer-slot-btn" type="button" data-slot="Afternoon">Afternoon</button>
        <button class="btn btn-ghost prayer-slot-btn" type="button" data-slot="Evening">Evening</button>
    </div>

    <div class="panel" style="margin-top: 1rem;">
        <div class="section-title">
            <h3 id="prayerSlotTitle" style="margin: 0;"><?= e($activeSlot) ?> Prayer</h3>
        </div>
        <p id="prayerSlotMessage"><?= e($currentPrayer ?? 'Prayer will appear here automatically.') ?></p>
        <div class="actions">
            <button class="btn btn-primary" type="button" id="playPrayerVoice">Play AI Voice</button>
        </div>
    </div>
</section>

<script>
    (() => {
        const prayersBySlot = <?= json_encode($slotPrayers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const defaultSlot = <?= json_encode($activeSlot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        const slotButtons = Array.from(document.querySelectorAll('.prayer-slot-btn'));
        const slotTitle = document.getElementById('prayerSlotTitle');
        const slotMessage = document.getElementById('prayerSlotMessage');
        const playButton = document.getElementById('playPrayerVoice');

        if (!slotTitle || !slotMessage || !playButton || slotButtons.length === 0) {
            return;
        }

        let selectedSlot = defaultSlot;
        let voiceTimer = null;

        const cleanMessage = (value) => String(value || '').replace(/^\w+ Prayer:\s*/, '').trim();

        function buildVoicePrayerText(slot, rawMessage) {
            const core = cleanMessage(rawMessage);
            if (!core) {
                return '';
            }

            const slotLines = {
                Morning: [
                    'Almighty Father, we dedicate this new day to your holy will.',
                    'Set our thoughts on what is pure, our words on what gives life, and our actions on what reflects your mercy.',
                    'Give us wisdom for every decision, courage for every challenge, and kindness for every person we meet.',
                ],
                Afternoon: [
                    'Almighty Father, in this midday hour renew our strength and steady our hearts.',
                    'Keep us patient in responsibility, faithful in service, and truthful in every conversation.',
                    'Let your peace quiet our worries, and let your guidance lead us in each task before us.',
                ],
                Evening: [
                    'Almighty Father, as evening comes we thank you for sustaining us through the day.',
                    'Wash away fear and weariness, restore joy to our spirits, and cover our homes with your peace.',
                    'Grant restful sleep, protect every family, and prepare us to rise again with grateful hearts.',
                ],
            };

            const closingLines = [
                'Teach us to forgive quickly, to speak gently, and to walk humbly before you.',
                'Let your truth remain in our hearts, and let your compassion flow through our lives.',
                'We trust your faithful love, and we honor your Name with thanksgiving. Amen.',
            ];

            const lines = [slot + ' prayer.', core]
                .concat(slotLines[slot] || slotLines.Morning)
                .concat(closingLines);

            return lines.join(' ');
        }

        function renderSlot(slot) {
            selectedSlot = slot;
            slotTitle.textContent = slot + ' Prayer';
            slotMessage.textContent = prayersBySlot[slot] || 'Prayer is not available yet for this slot.';

            slotButtons.forEach((button) => {
                const isActive = button.getAttribute('data-slot') === slot;
                button.classList.toggle('is-active', isActive);
            });
        }

        slotButtons.forEach((button) => {
            button.addEventListener('click', () => {
                renderSlot(button.getAttribute('data-slot') || defaultSlot);
            });
        });

        playButton.addEventListener('click', () => {
            const rawMessage = prayersBySlot[selectedSlot] || '';
            const text = buildVoicePrayerText(selectedSlot, rawMessage);

            if (!text || !('speechSynthesis' in window)) {
                alert('Voice playback is not available in this browser.');
                return;
            }

            window.speechSynthesis.cancel();
            if (voiceTimer) {
                clearTimeout(voiceTimer);
                voiceTimer = null;
            }

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'en-PH';
            utterance.rate = 0.9;
            utterance.pitch = 1;
            utterance.onend = () => {
                if (voiceTimer) {
                    clearTimeout(voiceTimer);
                    voiceTimer = null;
                }
            };

            window.speechSynthesis.speak(utterance);

            // Keep prayer voice playback in a fixed 30-second window.
            voiceTimer = setTimeout(() => {
                window.speechSynthesis.cancel();
                voiceTimer = null;
            }, 30000);
        });

        renderSlot(defaultSlot);
    })();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
