<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Pricing: R450 call-out fee + per-km rate, based on vehicle type.
$rates = [
    ['type' => 'Bike',        'label' => 'Bike',          'base' => 450, 'perKm' => 15, 'icon' => '&#127949;'],
    ['type' => 'Car',         'label' => 'Car',            'base' => 450, 'perKm' => 20, 'icon' => '&#128663;'],
    ['type' => 'SUV/Bakkie',  'label' => 'SUV / Bakkie',   'base' => 450, 'perKm' => 25, 'icon' => '&#128665;'],
    ['type' => 'Other',       'label' => 'Other',          'base' => 450, 'perKm' => 30, 'icon' => '&#128295;'],
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
    $notes           = trim($_POST['notes'] ?? '');

    if ($name === '') $errors[] = 'Name is required.';
    if ($phone === '') $errors[] = 'Phone is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (!isset($ratesByType[$vehicleType])) $errors[] = 'Please select a valid vehicle type.';
    if ($pickupLocation === '') $errors[] = 'Pickup location is required.';
    if ($dropoffLocation === '') $errors[] = 'Drop-off location is required.';
    if ($distanceKm < 0 || $distanceKm > 2000) $errors[] = 'Enter a realistic distance in km.';

    if (empty($errors)) {
        $rate = $ratesByType[$vehicleType];
        $estimatedCost = $rate['base'] + ($rate['perKm'] * $distanceKm);

        $stmt = db()->prepare(
            'INSERT INTO towing_requests (name, phone, email, vehicle_type, pickup_location, dropoff_location, distance_km, estimated_cost, notes, created_at, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)'
        );
        $stmt->execute([$name, $phone, $email, $vehicleType, $pickupLocation, $dropoffLocation, $distanceKm, $estimatedCost, $notes ?: null, 'Pending']);

        $success = sprintf(
            'Your towing application has been submitted. Estimated cost: R%s. Our team will contact you shortly to confirm.',
            number_format($estimatedCost, 2)
        );
    } else {
        old_set($_POST);
    }
}

$pageTitle = 'Towing - ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <h2>Towing Application</h2>
    <p class="text-muted-custom mb-0">
        24hr emergency towing from Dani Group. Choose your vehicle type below, apply, and we'll be on our way.
    </p>
</div>

<div class="row g-4 mb-4">
    <?php foreach ($rates as $rate): ?>
        <div class="col-6 col-lg-3">
            <div class="pricing-card h-100 text-center" data-vehicle="<?= e($rate['type']) ?>">
                <div class="pricing-icon"><?= $rate['icon'] ?></div>
                <h4 class="pricing-title"><?= e($rate['label']) ?></h4>
                <div class="pricing-amount">R<?= (int) $rate['base'] ?></div>
                <div class="pricing-sub">call-out fee</div>
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
    <h3 class="mb-3">Apply for Towing</h3>
    <p class="text-muted-custom">
        Fill in your details below. Select a vehicle type and enter the estimated distance to see your quote instantly.
    </p>

    <form action="/towing.php" method="post" id="towingForm">
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
                    <option value="<?= e($rate['type']) ?>" data-base="<?= (int) $rate['base'] ?>" data-perkm="<?= (int) $rate['perKm'] ?>">
                        <?= e($rate['label']) ?> (R<?= (int) $rate['base'] ?> + R<?= (int) $rate['perKm'] ?>/km)
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

        <div class="mb-3">
            <label>Distance (km)</label>
            <input type="number" name="distance_km" step="0.1" min="0" class="form-control" id="distanceKm" placeholder="Estimated distance in km" value="<?= old('distance_km') ?>">
        </div>

        <div class="mb-3">
            <label>Additional Notes</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Anything else we should know? (optional)"><?= old('notes') ?></textarea>
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
        var quoteBox = document.getElementById('quoteBox');
        var quoteAmount = document.getElementById('quoteAmount');
        var cards = document.querySelectorAll('.pricing-card');

        function updateQuote() {
            var option = vehicleSelect.options[vehicleSelect.selectedIndex];
            var baseFee = parseFloat(option ? option.getAttribute('data-base') : '') || 0;
            var perKm = parseFloat(option ? option.getAttribute('data-perkm') : '') || 0;
            var distance = parseFloat(distanceInput.value) || 0;

            cards.forEach(function (card) {
                card.classList.toggle('pricing-card-active', vehicleSelect.value && card.getAttribute('data-vehicle') === vehicleSelect.value);
            });

            if (!vehicleSelect.value) {
                quoteBox.style.display = 'none';
                return;
            }

            var total = baseFee + (perKm * distance);
            quoteAmount.textContent = 'R' + total.toFixed(2);
            quoteBox.style.display = 'flex';
        }

        vehicleSelect.addEventListener('change', updateQuote);
        distanceInput.addEventListener('input', updateQuote);

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
