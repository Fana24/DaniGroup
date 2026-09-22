<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Pricing benchmarked against SA removal companies. Call-out fee + hourly on-site
// rate (with a minimum) + per-km travel rate.
$rates = [
    ['type' => 'MiniVan',     'label' => 'Mini Van',       'bestFor' => 'A few boxes or a single item', 'callout' => 550,  'hourly' => 120, 'minHours' => 2, 'perKm' => 7,  'icon' => '&#128667;'],
    ['type' => 'Bakkie1Ton',  'label' => '1 Ton Bakkie',   'bestFor' => 'Bachelor flat or studio move', 'callout' => 650,  'hourly' => 180, 'minHours' => 2, 'perKm' => 9,  'icon' => '&#128667;'],
    ['type' => 'Truck4Ton',   'label' => '4 Ton Truck',    'bestFor' => '2-3 bedroom home',              'callout' => 900,  'hourly' => 280, 'minHours' => 3, 'perKm' => 12, 'icon' => '&#128666;'],
    ['type' => 'Truck8Ton',   'label' => '8 Ton Truck',    'bestFor' => 'Large home or office move',     'callout' => 1300, 'hourly' => 420, 'minHours' => 3, 'perKm' => 16, 'icon' => '&#128666;'],
];
$ratesByType = array_column($rates, null, 'type');

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name            = trim($_POST['name'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $vehicleType     = trim($_POST['vehicle_type'] ?? '');
    $pickupLocation  = trim($_POST['pickup_location'] ?? '');
    $dropoffLocation = trim($_POST['dropoff_location'] ?? '');
    $distanceKm      = (float) ($_POST['distance_km'] ?? 0);
    $estimatedHours  = (float) ($_POST['estimated_hours'] ?? 0);
    $preferredDate   = trim($_POST['preferred_date'] ?? '');
    $notes           = trim($_POST['notes'] ?? '');

    if ($name === '') $errors[] = 'Name is required.';
    if ($phone === '') $errors[] = 'Phone is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (!isset($ratesByType[$vehicleType])) $errors[] = 'Please select a valid vehicle type.';
    if ($pickupLocation === '') $errors[] = 'Pickup location is required.';
    if ($dropoffLocation === '') $errors[] = 'Drop-off location is required.';
    if ($distanceKm < 0 || $distanceKm > 2000) $errors[] = 'Enter a realistic distance in km.';
    if ($estimatedHours < 0 || $estimatedHours > 48) $errors[] = 'Enter a realistic number of hours.';

    if (empty($errors)) {
        $rate = $ratesByType[$vehicleType];
        $billedHours = max($estimatedHours, $rate['minHours']);
        $estimatedCost = $rate['callout'] + ($rate['hourly'] * $billedHours) + ($rate['perKm'] * $distanceKm);

        $stmt = db()->prepare(
            'INSERT INTO moving_requests (name, phone, email, vehicle_type, pickup_location, dropoff_location, distance_km, estimated_hours, preferred_date, estimated_cost, notes, created_at, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)'
        );
        $stmt->execute([
            $name, $phone, $email, $vehicleType, $pickupLocation, $dropoffLocation,
            $distanceKm, $estimatedHours, $preferredDate ?: null, $estimatedCost, $notes ?: null, 'Pending',
        ]);

        $success = sprintf(
            'Your furniture moving request has been submitted. Estimated cost: R%s (min %s hr on-site). Our team will confirm your booking shortly.',
            number_format($estimatedCost, 2),
            rtrim(rtrim(number_format($rate['minHours'], 1), '0'), '.')
        );
    } else {
        old_set($_POST);
    }
}

$pageTitle = 'Furniture Moving - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <h2>Furniture Moving Application</h2>
    <p class="text-muted-custom mb-0">
        From a single item to a full house move. Choose the right vehicle below, apply, and we'll handle the rest.
    </p>
</div>

<div class="row g-4 mb-4">
    <?php foreach ($rates as $rate): ?>
        <div class="col-6 col-lg-3">
            <div class="pricing-card h-100 text-center" data-vehicle="<?= e($rate['type']) ?>">
                <div class="pricing-icon"><?= $rate['icon'] ?></div>
                <h4 class="pricing-title"><?= e($rate['label']) ?></h4>
                <div class="pricing-sub mb-1"><?= e($rate['bestFor']) ?></div>
                <div class="pricing-amount">R<?= (int) $rate['callout'] ?></div>
                <div class="pricing-sub">call-out fee</div>
                <div class="pricing-rate">+ R<?= (int) $rate['hourly'] ?> / hr (min <?= (int) $rate['minHours'] ?>hr)</div>
                <div class="pricing-rate">+ R<?= (int) $rate['perKm'] ?> / km</div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($success): ?>
    <div class="alert alert-success" role="alert"><?= e($success) ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="form-box">
    <h3 class="mb-3">Apply for Furniture Moving</h3>
    <p class="text-muted-custom">
        Fill in your details below. Select a vehicle, estimated hours and distance to see your quote instantly.
    </p>

    <form action="/moving.php" method="post" id="movingForm">
        <?= csrf_field() ?>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" placeholder="Your full name" value="<?= old('name') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" placeholder="e.g. 082 123 4567" value="<?= old('phone') ?>">
            </div>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= old('email') ?>">
        </div>

        <div class="mb-3">
            <label>Vehicle Type</label>
            <select name="vehicle_type" class="form-select" id="vehicleType">
                <option value="">-- Select vehicle type --</option>
                <?php foreach ($rates as $rate): ?>
                    <option value="<?= e($rate['type']) ?>"
                            data-callout="<?= (int) $rate['callout'] ?>"
                            data-hourly="<?= (int) $rate['hourly'] ?>"
                            data-minhours="<?= (int) $rate['minHours'] ?>"
                            data-perkm="<?= (int) $rate['perKm'] ?>">
                        <?= e($rate['label']) ?> - R<?= (int) $rate['callout'] ?> + R<?= (int) $rate['hourly'] ?>/hr (min <?= (int) $rate['minHours'] ?>hr) + R<?= (int) $rate['perKm'] ?>/km
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Pickup Location</label>
                <div class="location-input-wrap">
                    <input type="text" name="pickup_location" class="form-control" placeholder="Start typing an address..." autocomplete="off" data-address-autocomplete="true" value="<?= old('pickup_location') ?>">
                    <span class="location-input-icon">&#128205;</span>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label>Drop-off Location</label>
                <div class="location-input-wrap">
                    <input type="text" name="dropoff_location" class="form-control" placeholder="Start typing an address..." autocomplete="off" data-address-autocomplete="true" value="<?= old('dropoff_location') ?>">
                    <span class="location-input-icon">&#128205;</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Distance (km)</label>
                <input type="number" name="distance_km" step="0.1" min="0" class="form-control" id="distanceKm" placeholder="Distance in km" value="<?= old('distance_km') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label>Estimated Hours On-Site</label>
                <input type="number" name="estimated_hours" step="0.5" min="0" class="form-control" id="estimatedHours" placeholder="Hours on-site" value="<?= old('estimated_hours') ?>">
                <small class="text-muted-custom" id="minHoursHint"></small>
            </div>
            <div class="col-md-4 mb-3">
                <label>Preferred Moving Date</label>
                <input type="date" name="preferred_date" class="form-control" value="<?= old('preferred_date') ?>">
            </div>
        </div>

        <div class="mb-3">
            <label>Additional Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="e.g. stairs, large items, number of rooms (optional)"><?= old('notes') ?></textarea>
        </div>

        <div class="quote-box mb-3" id="quoteBox" style="display:none;">
            <span class="quote-label">Estimated Cost</span>
            <span class="quote-amount" id="quoteAmount">R0.00</span>
        </div>

        <button type="submit" class="btn btn-dani-primary">Submit Application</button>
    </form>
</div>

<?php old_clear(); ?>

<?php
$extraScripts = <<<'HTML'
<script>
    (function () {
        var vehicleSelect = document.getElementById('vehicleType');
        var distanceInput = document.getElementById('distanceKm');
        var hoursInput = document.getElementById('estimatedHours');
        var minHoursHint = document.getElementById('minHoursHint');
        var quoteBox = document.getElementById('quoteBox');
        var quoteAmount = document.getElementById('quoteAmount');
        var cards = document.querySelectorAll('.pricing-card');

        function updateQuote() {
            var option = vehicleSelect.options[vehicleSelect.selectedIndex];
            var calloutFee = parseFloat(option ? option.getAttribute('data-callout') : '') || 0;
            var hourlyRate = parseFloat(option ? option.getAttribute('data-hourly') : '') || 0;
            var minHours = parseFloat(option ? option.getAttribute('data-minhours') : '') || 0;
            var perKm = parseFloat(option ? option.getAttribute('data-perkm') : '') || 0;
            var distance = parseFloat(distanceInput.value) || 0;
            var hours = parseFloat(hoursInput.value) || 0;

            cards.forEach(function (card) {
                card.classList.toggle('pricing-card-active', vehicleSelect.value && card.getAttribute('data-vehicle') === vehicleSelect.value);
            });

            if (!vehicleSelect.value) {
                quoteBox.style.display = 'none';
                minHoursHint.textContent = '';
                return;
            }

            minHoursHint.textContent = 'Minimum ' + minHours + ' hour(s) billed for this vehicle.';

            var billedHours = Math.max(hours, minHours);
            var total = calloutFee + (hourlyRate * billedHours) + (perKm * distance);
            quoteAmount.textContent = 'R' + total.toFixed(2);
            quoteBox.style.display = 'flex';
        }

        vehicleSelect.addEventListener('change', updateQuote);
        distanceInput.addEventListener('input', updateQuote);
        hoursInput.addEventListener('input', updateQuote);

        cards.forEach(function (card) {
            card.addEventListener('click', function () {
                vehicleSelect.value = card.getAttribute('data-vehicle');
                updateQuote();
                vehicleSelect.focus();
            });
        });

        updateQuote();
    })();
</script>
HTML;
require __DIR__ . '/includes/footer.php';
?>
