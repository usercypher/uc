
// Add this on every file you want to profile
declare(ticks=1);

// Add this on entry point file
require('TickProfiler.php');
$tickProfiler = new TickProfiler();
$tickProfiler->init('TickProfiler.log');