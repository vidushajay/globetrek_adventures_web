<?php
// providers_section.php
// Shared HTML for staff_dashboard.php and admin_dashboard.php: provider directory + per-booking arrangements.
// Expects $conn.

$providers = $conn->query("SELECT * FROM service_providers ORDER BY type, name");
$hotels = [];
$transports = [];
$all_providers = [];
while ($pr = $providers->fetch_assoc()) {
    $all_providers[] = $pr;
    if ($pr['type'] === 'hotel') { $hotels[] = $pr; } else { $transports[] = $pr; }
}

$arrangements = $conn->query(
    "SELECT bookings.booking_id, bookings.travel_date, bookings.num_travelers, bookings.status,
            bookings.traveler_title, bookings.first_name, bookings.last_name,
            bookings.hotel_provider_id, bookings.transport_provider_id,
            bookings.arrangement_status, bookings.arrangement_notes,
            packages.title
     FROM bookings
     JOIN packages ON bookings.package_id = packages.package_id
     WHERE bookings.status <> 'cancelled'
     ORDER BY bookings.travel_date ASC"
);
$arr_labels = ['not_arranged' => 'Not arranged', 'requested' => 'Requested', 'confirmed' => 'Confirmed'];
?>

    <!-- ================= HOTELS & TRANSPORT PROVIDERS ================= -->
    <h2 class="secondary-headings" id="providers-section">Hotels &amp; Transport Providers</h2>

    <form method="POST" action="#providers-section" class="dashboard-form">
        <input type="hidden" name="action" value="add_provider">

        <label for="provider_type">Type</label>
        <select id="provider_type" name="provider_type">
            <option value="hotel">Hotel</option>
            <option value="transport">Transport provider</option>
        </select>

        <label for="provider_name">Name</label>
        <input type="text" id="provider_name" name="provider_name" maxlength="150" required>

        <label for="provider_phone">Phone</label>
        <input type="text" id="provider_phone" name="provider_phone" maxlength="30">

        <label for="provider_email">Email</label>
        <input type="email" id="provider_email" name="provider_email" maxlength="150">

        <label for="provider_notes">Notes (rates, contact person...)</label>
        <input type="text" id="provider_notes" name="provider_notes" maxlength="500">

        <button type="submit" class="primary-btn small-btn" style="font-size:1rem; padding:0.7rem 1.8rem;">Add Provider</button>
    </form>

    <div class="table-wrap">
    <table class="dash-table">
        <thead>
            <tr><th>Type</th><th>Name</th><th>Phone</th><th>Email</th><th>Notes</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php if (empty($all_providers)): ?>
            <tr><td colspan="6">No providers yet. Add your first hotel or transport provider above.</td></tr>
        <?php endif; ?>
        <?php foreach ($all_providers as $pr): ?>
            <tr>
                <td><?php echo $pr['type'] === 'hotel' ? 'Hotel' : 'Transport'; ?></td>
                <td><?php echo htmlspecialchars($pr['name']); ?></td>
                <td><?php echo $pr['phone'] !== '' ? '<a href="tel:' . htmlspecialchars($pr['phone']) . '">' . htmlspecialchars($pr['phone']) . '</a>' : '&mdash;'; ?></td>
                <td><?php echo $pr['email'] !== '' ? '<a href="mailto:' . htmlspecialchars($pr['email']) . '">' . htmlspecialchars($pr['email']) . '</a>' : '&mdash;'; ?></td>
                <td><?php echo htmlspecialchars($pr['notes'] ?? ''); ?></td>
                <td>
                    <form method="POST" action="#providers-section" class="inline-form" onsubmit="return confirm('Remove this provider? Bookings using it will become unassigned.');">
                        <input type="hidden" name="action" value="delete_provider">
                        <input type="hidden" name="provider_id" value="<?php echo (int)$pr['provider_id']; ?>">
                        <button type="submit" class="small-btn" style="background-color:#dc3545;">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- ================= TRAVEL ARRANGEMENTS ================= -->
    <h2 class="secondary-headings" id="arrangements-section">Travel Arrangements (hotel &amp; transport per booking)</h2>
    <div class="table-wrap">
    <table class="dash-table">
        <thead>
            <tr><th>#</th><th>Traveler</th><th>Package</th><th>Travel Date</th><th>Hotel</th><th>Transport</th><th>Arrangement</th><th>Notes</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php if ($arrangements->num_rows === 0): ?>
            <tr><td colspan="9">No active bookings.</td></tr>
        <?php endif; ?>
        <?php while ($a = $arrangements->fetch_assoc()): ?>
            <tr>
                <td><?php echo (int)$a['booking_id']; ?></td>
                <td><?php echo htmlspecialchars(trim($a['traveler_title'] . ' ' . $a['first_name'] . ' ' . $a['last_name'])); ?><br><small><?php echo (int)$a['num_travelers']; ?> traveler(s)</small></td>
                <td><?php echo htmlspecialchars($a['title']); ?></td>
                <td><?php echo htmlspecialchars($a['travel_date']); ?></td>
                <td>
                    <select name="hotel_id" form="arr-form-<?php echo (int)$a['booking_id']; ?>">
                        <option value="0">&mdash; none &mdash;</option>
                        <?php foreach ($hotels as $h): ?>
                            <option value="<?php echo (int)$h['provider_id']; ?>" <?php echo (int)$a['hotel_provider_id'] === (int)$h['provider_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($h['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <select name="transport_id" form="arr-form-<?php echo (int)$a['booking_id']; ?>">
                        <option value="0">&mdash; none &mdash;</option>
                        <?php foreach ($transports as $t): ?>
                            <option value="<?php echo (int)$t['provider_id']; ?>" <?php echo (int)$a['transport_provider_id'] === (int)$t['provider_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <select name="arrangement_status" form="arr-form-<?php echo (int)$a['booking_id']; ?>">
                        <?php foreach ($arr_labels as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo $a['arrangement_status'] === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <textarea name="arrangement_notes" form="arr-form-<?php echo (int)$a['booking_id']; ?>" rows="2" maxlength="500" placeholder="Pickup time, room type..." style="width:100%; min-width:140px; font-size:0.8rem; padding:0.3rem;"><?php echo htmlspecialchars($a['arrangement_notes'] ?? ''); ?></textarea>
                </td>
                <td>
                    <form method="POST" action="#arrangements-section" class="inline-form" id="arr-form-<?php echo (int)$a['booking_id']; ?>">
                        <input type="hidden" name="action" value="save_arrangement">
                        <input type="hidden" name="booking_id" value="<?php echo (int)$a['booking_id']; ?>">
                        <button type="submit" class="small-btn">Save</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
