<?php
session_start();

// --- Configuration ---
// SECURITY: To avoid hardcoding your plain-text password, generate a password hash
// (e.g., using password_hash('MySecurePass', PASSWORD_DEFAULT) in a separate script)
// and place it in $adminPasswordHash below. Then set $adminPasswordPlain to empty.
$adminPasswordHash = ''; 
$adminPasswordPlain = 'AdminPassword123'; // Clear this once you have a hash set
$dataFile = 'events.json';
// ---------------------

$error = '';
$success = '';

// Initialize data file if it doesn't exist
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}

// Read events
$eventsRaw = file_get_contents($dataFile);
$events = json_decode($eventsRaw, true);
if (!is_array($events)) {
    $events = [];
}

// Ensure loaded events are sorted for display
usort($events, function($a, $b) {
    $dateA = isset($a['sort_date']) && $a['sort_date'] !== '' ? $a['sort_date'] : '9999-12-31';
    $dateB = isset($b['sort_date']) && $b['sort_date'] !== '' ? $b['sort_date'] : '9999-12-31';
    return strcmp($dateA, $dateB);
});

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $inputPassword = $_POST['password'];
    $isValid = false;
    
    if (!empty($adminPasswordHash) && password_verify($inputPassword, $adminPasswordHash)) {
        $isValid = true;
    } elseif (!empty($adminPasswordPlain) && $inputPassword === $adminPasswordPlain) {
        $isValid = true;
    }

    if ($isValid) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = 'Incorrect password.';
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin-events.php");
    exit;
}

// Check logged-in status
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Handle Adding an Event
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $newEvent = [
        'id' => uniqid(),
        'name' => htmlspecialchars($_POST['name']),
        'dates' => htmlspecialchars($_POST['dates']),
        'sort_date' => htmlspecialchars($_POST['sort_date'] ?? ''),
        'location' => htmlspecialchars($_POST['location']),
        'link' => htmlspecialchars($_POST['link'] ?? '')
    ];
    
    $events[] = $newEvent;
    
    // Sort events by date before saving
    usort($events, function($a, $b) {
        $dateA = isset($a['sort_date']) && $a['sort_date'] !== '' ? $a['sort_date'] : '9999-12-31';
        $dateB = isset($b['sort_date']) && $b['sort_date'] !== '' ? $b['sort_date'] : '9999-12-31';
        return strcmp($dateA, $dateB);
    });

    file_put_contents($dataFile, json_encode($events, JSON_PRETTY_PRINT));
    $success = 'Event added successfully.';
}

// Handle Deleting an Event
if ($isLoggedIn && isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    
    // Filter out the deleted event
    $events = array_filter($events, function($event) use ($deleteId) {
        return $event['id'] !== $deleteId;
    });
    
    // Reindex array
    $events = array_values($events);
    file_put_contents($dataFile, json_encode($events, JSON_PRETTY_PRINT));
    
    header("Location: admin-events.php"); // Redirect to clean the URL
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Events</title>
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Prevent Flash of Unstyled Content against Tailwind CDN */
        html { visibility: hidden; }
    </style>
    <script>
        // Make visible once the elements and script are loaded
        window.addEventListener('load', function() {
            document.documentElement.style.visibility = 'visible';
        });
    </script>
</head>
<body class="bg-gray-100 p-8 font-sans">

<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <div class="border-b pb-4 mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Squadron Events Admin</h1>
        <p class="text-gray-500">Manage the events shown to parents.</p>
    </div>

    <?php if (!$isLoggedIn): ?>
        <!-- Login Form -->
        <div class="max-w-sm mx-auto mt-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Please Login</h2>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-1">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submit" name="login" class="w-full bg-blue-600 text-white font-bold px-4 py-2 rounded shadow hover:bg-blue-700 transition">Login</button>
            </form>
        </div>
        
    <?php else: ?>
        <!-- Dashboard View -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-800">Add New Event</h2>
            <a href="?logout=1" class="text-red-500 hover:text-red-700 font-medium bg-red-50 hover:bg-red-100 px-3 py-1 rounded transition">Logout</a>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Add Event Form -->
        <form method="POST" action="" class="bg-gray-50 p-5 rounded-lg border mb-10 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Name of Event *</label>
                    <input type="text" name="name" required placeholder="e.g. Navigation Training" class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Display Dates *</label>
                    <input type="text" name="dates" required placeholder="e.g. 15-17 Oct 2026" class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Start Date (For List Order) *</label>
                    <input type="date" name="sort_date" required class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Location *</label>
                    <input type="text" name="location" required placeholder="e.g. Squadron HQ" class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Cadet Portal Link (Optional)</label>
                    <input type="url" name="link" placeholder="https://cadetportal.example.com/..." class="w-full px-3 py-2 border rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <button type="submit" name="add_event" class="mt-4 bg-green-600 text-white px-6 py-2 rounded shadow hover:bg-green-700 font-bold transition">Add Event</button>
        </form>

        <!-- Current Events Table -->
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Current Events</h2>
        <div class="overflow-x-auto shadow-sm rounded-lg border border-gray-200">
            <table class="min-w-full bg-white divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Portal Link</th>
                        <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($events)): ?>
                        <tr><td colspan="5" class="py-6 px-4 text-center text-gray-500">No events listed yet. Fill out the form above.</td></tr>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-3 px-4 text-sm text-gray-900 font-medium"><?php echo $event['name']; ?></td>
                                <td class="py-3 px-4 text-sm text-gray-600"><?php echo $event['dates']; ?></td>
                                <td class="py-3 px-4 text-sm text-gray-600"><?php echo $event['location']; ?></td>
                                <td class="py-3 px-4 text-sm">
                                    <?php if (!empty($event['link'])): ?>
                                        <a href="<?php echo $event['link']; ?>" target="_blank" class="text-blue-600 hover:underline hover:text-blue-800">View on Portal</a>
                                    <?php else: ?>
                                        <span class="text-gray-400 italic">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-sm text-center">
                                    <a href="?delete=<?php echo urlencode($event['id']); ?>" onclick="return confirm('Are you sure you want to delete this event?');" class="text-red-500 hover:text-red-700 font-medium px-3 py-1 bg-red-50 rounded hover:bg-red-100 transition">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<footer class="mt-12 text-center text-sm text-gray-500 py-6 border-t border-gray-200 w-full max-w-4xl mx-auto">
    <p>&copy; <?php echo date('Y'); ?> Andrew Jolley & 309 Squadron. All rights reserved.</p>
    <p class="mt-2"><a href="privacy-policy.html" class="text-blue-600 hover:text-blue-800 hover:underline transition">Privacy Policy</a></p>
</footer>

</body>
</html>