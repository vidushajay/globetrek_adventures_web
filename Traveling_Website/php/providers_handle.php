<?php
// providers_handle.php
// Shared by staff_dashboard.php and admin_dashboard.php (include AFTER the login/role check).
// Handles: add_provider, delete_provider, save_arrangement.
// Expects $conn, $errors and $notices to already exist.

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {

    // ---- Add a hotel / transport provider ----
    if ($_POST['action'] === 'add_provider') {
        $ptype  = $_POST['provider_type'] ?? '';
        $pname  = trim($_POST['provider_name'] ?? '');
        $pphone = mb_substr(trim($_POST['provider_phone'] ?? ''), 0, 30);
        $pemail = trim($_POST['provider_email'] ?? '');
        $pnotes = mb_substr(trim($_POST['provider_notes'] ?? ''), 0, 500);

        if (!in_array($ptype, ['hotel', 'transport'], true)) {
            $errors[] = "Please choose a provider type (Hotel or Transport).";
        } elseif ($pname === '') {
            $errors[] = "Provider name is required.";
        } elseif ($pemail !== '' && !filter_var($pemail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid provider email address.";
        } else {
            $stmt = $conn->prepare("INSERT INTO service_providers (name, type, phone, email, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $pname, $ptype, $pphone, $pemail, $pnotes);
            if ($stmt->execute()) {
                $notices[] = "Provider '$pname' added.";
            } else {
                $errors[] = "Failed to add provider.";
            }
            $stmt->close();
        }
    }

    // ---- Delete a provider (bookings that used it simply become 'not assigned') ----
    elseif ($_POST['action'] === 'delete_provider') {
        $provider_id = (int)($_POST['provider_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM service_providers WHERE provider_id = ?");
        $stmt->bind_param("i", $provider_id);
        if ($stmt->execute()) {
            $notices[] = "Provider removed.";
        } else {
            $errors[] = "Failed to remove provider.";
        }
        $stmt->close();
    }

    // ---- Save hotel / transport arrangement for one booking ----
    elseif ($_POST['action'] === 'save_arrangement') {
        $booking_id   = (int)($_POST['booking_id'] ?? 0);
        $hotel_id     = (int)($_POST['hotel_id'] ?? 0);
        $transport_id = (int)($_POST['transport_id'] ?? 0);
        $arr_status   = $_POST['arrangement_status'] ?? '';
        $arr_notes    = mb_substr(trim($_POST['arrangement_notes'] ?? ''), 0, 500);

        $valid = in_array($arr_status, ['not_arranged', 'requested', 'confirmed'], true);

        // A chosen provider must exist and be of the right type; 0 means "none".
        $check = $conn->prepare("SELECT provider_id FROM service_providers WHERE provider_id = ? AND type = ?");
        foreach ([[$hotel_id, 'hotel'], [$transport_id, 'transport']] as $pair) {
            if ($pair[0] !== 0) {
                $check->bind_param("is", $pair[0], $pair[1]);
                $check->execute();
                if ($check->get_result()->num_rows === 0) { $valid = false; }
            }
        }
        $check->close();

        if (!$valid) {
            $errors[] = "Invalid hotel, transport or arrangement status.";
        } else {
            $h = $hotel_id === 0 ? null : $hotel_id;
            $t = $transport_id === 0 ? null : $transport_id;
            $stmt = $conn->prepare(
                "UPDATE bookings
                 SET hotel_provider_id = ?, transport_provider_id = ?, arrangement_status = ?, arrangement_notes = ?
                 WHERE booking_id = ?"
            );
            $stmt->bind_param("iissi", $h, $t, $arr_status, $arr_notes, $booking_id);
            if ($stmt->execute()) {
                $notices[] = "Travel arrangements for booking #$booking_id saved.";
            } else {
                $errors[] = "Failed to save arrangements for booking #$booking_id.";
            }
            $stmt->close();
        }
    }
}
