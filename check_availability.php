<?php
/**
 * Check Court Availability
 * Returns available time slots for a specific court and date
 */

require_once __DIR__ . '/includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$courtId = (int) ($_GET['court_id'] ?? 0);
$date = $_GET['date'] ?? '';

if (!$courtId || !$date) {
    echo json_encode(['error' => 'Court ID and date are required']);
    exit;
}

$db = getDB();

// Get all existing reservations for this court and date
$stmt = $db->prepare(
    'SELECT start_time, end_time, hours_reserved, reservation_code
     FROM exclusive_reservations
     WHERE court_id = ? AND reservation_date = ?
     AND status IN (\'pending\', \'approved\')
     ORDER BY start_time ASC'
);
$stmt->execute([$courtId, $date]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Operating hours: 5:00 AM - 10:00 PM
$operatingStart = '05:00:00';
$operatingEnd = '22:00:00';

// Build array of booked time slots
$bookedSlots = [];
foreach ($reservations as $res) {
    $bookedSlots[] = [
        'start' => $res['start_time'],
        'end' => $res['end_time'],
        'hours' => $res['hours_reserved'],
        'code' => $res['reservation_code']
    ];
}

// Calculate available slots
$availableSlots = [];
if (empty($bookedSlots)) {
    // Entire day available
    $availableSlots[] = [
        'start' => $operatingStart,
        'end' => $operatingEnd,
        'duration' => '17 hours'
    ];
} else {
    // Check for slot before first booking
    if ($bookedSlots[0]['start'] > $operatingStart) {
        $availableSlots[] = [
            'start' => $operatingStart,
            'end' => $bookedSlots[0]['start'],
            'duration' => calculateDuration($operatingStart, $bookedSlots[0]['start'])
        ];
    }
    
    // Check for slots between bookings
    for ($i = 0; $i < count($bookedSlots) - 1; $i++) {
        $currentEnd = $bookedSlots[$i]['end'];
        $nextStart = $bookedSlots[$i + 1]['start'];
        
        if ($currentEnd < $nextStart) {
            $availableSlots[] = [
                'start' => $currentEnd,
                'end' => $nextStart,
                'duration' => calculateDuration($currentEnd, $nextStart)
            ];
        }
    }
    
    // Check for slot after last booking
    $lastBookingEnd = $bookedSlots[count($bookedSlots) - 1]['end'];
    if ($lastBookingEnd < $operatingEnd) {
        $availableSlots[] = [
            'start' => $lastBookingEnd,
            'end' => $operatingEnd,
            'duration' => calculateDuration($lastBookingEnd, $operatingEnd)
        ];
    }
}

echo json_encode([
    'success' => true,
    'court_id' => $courtId,
    'date' => $date,
    'booked_slots' => $bookedSlots,
    'available_slots' => $availableSlots,
    'operating_hours' => [
        'start' => $operatingStart,
        'end' => $operatingEnd
    ]
]);

function calculateDuration($start, $end) {
    $startTime = strtotime($start);
    $endTime = strtotime($end);
    $hours = ($endTime - $startTime) / 3600;
    return number_format($hours, 1) . ' hours';
}
