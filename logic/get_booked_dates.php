<?php
require_once '../config/db_connection.php';

header('Content-Type: application/json');

try {
    // Return all confirmed or pending bookings with their date ranges
    // These are the statuses that block a facility from being re-booked
    $sql = "
        SELECT bi.facility_id, bi.check_in_date, bi.check_out_date
        FROM bookings b
        JOIN booking_items bi ON b.booking_id = bi.booking_id
        JOIN booking_statuses bs ON b.booking_status_id = bs.booking_status_id
        WHERE bs.status_name IN ('Pending', 'Confirmed')
        ORDER BY bi.facility_id ASC, bi.check_in_date ASC
    ";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by facility_id
    $booked = [];
    foreach ($rows as $row) {
        $fid = (int)$row['facility_id'];
        if (!isset($booked[$fid])) {
            $booked[$fid] = [];
        }
        $booked[$fid][] = [
            'check_in'  => $row['check_in_date'],
            'check_out' => $row['check_out_date'],
        ];
    }

    echo json_encode([
        'success' => true,
        'booked'  => $booked,
    ]);

} catch (PDOException $e) {
    error_log('get_booked_dates error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'booked'  => [],
    ]);
}
