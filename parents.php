<?php
$dataFile = 'events.json';

// Read events safely
if (file_exists($dataFile)) {
    $eventsRaw = file_get_contents($dataFile);
    $events = json_decode($eventsRaw, true);
    if (!is_array($events)) {
        $events = [];
    }
} else {
    $events = [];
}

// Sort events by date
usort($events, function($a, $b) {
    $dateA = isset($a['sort_date']) && $a['sort_date'] !== '' ? $a['sort_date'] : '9999-12-31';
    $dateB = isset($b['sort_date']) && $b['sort_date'] !== '' ? $b['sort_date'] : '9999-12-31';
    return strcmp($dateA, $dateB);
});

// Filter out events that are in the past
$currentDate = date('Y-m-d');
$events = array_filter($events, function($event) use ($currentDate) {
    $date = isset($event['sort_date']) && $event['sort_date'] !== '' ? $event['sort_date'] : '9999-12-31';
    return $date >= $currentDate;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Cadet Events</title>
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Prevent Flash of Unstyled Content against Tailwind CDN */
        html { visibility: hidden; }
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
    <script>
        // Make visible once the elements and script are loaded
        window.addEventListener('load', function() {
            document.documentElement.style.visibility = 'visible';
        });
    </script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen py-10 antialiased">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center pb-8 border-b border-gray-200 mb-8">
            <!-- Optional image logo place if needed -->
            <!-- <img src="assets/img/logo.jpg" alt="Squadron Logo" class="h-16 mx-auto mb-4 rounded"> -->
            <h1 class="text-4xl font-extrabold text-blue-900 tracking-tight sm:text-5xl">Squadron Events</h1>
            <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">Upcoming activities and schedules for parents and cadets.</p>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 table-auto">
                    <thead class="bg-blue-50 text-blue-800">
                        <tr>
                            <th scope="col" class="py-4 px-6 text-left text-sm font-bold uppercase tracking-wider">Event Name</th>
                            <th scope="col" class="py-4 px-6 text-left text-sm font-bold uppercase tracking-wider">Dates</th>
                            <th scope="col" class="py-4 px-6 text-left text-sm font-bold uppercase tracking-wider">Location</th>
                            <th scope="col" class="py-4 px-6 text-center text-sm font-bold uppercase tracking-wider">Cadet Portal</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($events)): ?>
                            <tr>
                                <td colspan="4" class="py-12 px-6 text-center text-gray-500 text-lg">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <span>There are currently no upcoming events listed.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <tr class="hover:bg-blue-50 transition-colors duration-200">
                                    <td class="py-4 px-6">
                                        <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($event['name']); ?></div>
                                        <?php if (!empty($event['prereqs'])): ?>
                                            <div class="text-xs text-gray-500 mt-1 flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <span class="font-semibold mr-1">Requires:</span> <?php echo htmlspecialchars($event['prereqs']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <div class="inline-flex items-center text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full font-medium">
                                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <?php echo htmlspecialchars($event['dates']); ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center text-sm text-gray-700">
                                            <svg class="w-4 h-4 mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            <span><?php echo htmlspecialchars($event['location']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center whitespace-nowrap">
                                        <?php if (!empty($event['link'])): ?>
                                            <a href="<?php echo htmlspecialchars($event['link']); ?>" target="_blank" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm transition-colors duration-200">
                                                Cadet Portal
                                                <svg class="w-4 h-4 ml-2 -mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 text-sm text-gray-400 bg-gray-50 rounded-md italic">
                                                No link available
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($events)): ?>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 text-xs text-gray-500 text-right">
                Showing <?php echo count($events); ?> upcoming event(s).
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <footer class="mt-12 text-center text-sm text-gray-500 py-8 border-t border-gray-200">
            <p>&copy; <?php echo date('Y'); ?> Andrew Jolley & 309 Squadron. All rights reserved.</p>
            <p class="mt-3"><a href="privacy-policy.html" class="text-blue-600 hover:text-blue-800 hover:underline transition-colors duration-200">Privacy Policy</a></p>
        </footer>
    </div>
</body>
</html>