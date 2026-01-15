$user = \App\Models\User::whereNotNull('ssm_api_token')->first();
if (!$user) { echo "No user with token found.\n"; exit; }
echo "User: " . $user->name . " (" . $user->email . ")\n";
$token = $user->ssm_api_token;
echo "Token found: " . substr($token, 0, 5) . "...\n";

$controller = new \App\Http\Controllers\DashboardController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('fetchSSMWeekReport');
$method->setAccessible(true);

try {
    $data = $method->invoke($controller, $token);
    echo "Files Fetched:\n";
    print_r($data);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
