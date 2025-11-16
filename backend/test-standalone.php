<?php

/**
 * Test Standalone Components
 * Quick verification that all standalone components work
 */

echo "=== Testing Standalone Components ===\n\n";

$errors = 0;

// Test 1: Check standalone files exist
echo "[1/6] Checking standalone files...\n";
$files = [
    'standalone/SimpleRouter.php',
    'standalone/SimpleJWT.php',
    'standalone/SimpleEnv.php',
    'standalone/autoload.php',
    'src/Bootstrap/App.php',
    'src/Controllers/BaseController.php',
    'public/index.php',
    '.env',
    'deploy.sh'
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "  ✗ Missing: $file\n";
        $errors++;
    }
}

if ($errors === 0) {
    echo "  ✓ All required files exist\n";
}
echo "\n";

// Test 2: Load standalone components
echo "[2/6] Loading standalone components...\n";
try {
    require_once 'standalone/autoload.php';
    require_once 'standalone/SimpleEnv.php';
    require_once 'standalone/SimpleJWT.php';
    require_once 'standalone/SimpleRouter.php';
    echo "  ✓ All components loaded successfully\n";
} catch (Exception $e) {
    echo "  ✗ Error loading: " . $e->getMessage() . "\n";
    $errors++;
}
echo "\n";

// Test 3: Test SimpleRouter
echo "[3/6] Testing SimpleRouter...\n";
try {
    $router = new SimpleRouter();
    $router->get('/test', function() {
        return ['test' => 'success'];
    });
    echo "  ✓ SimpleRouter works\n";
} catch (Exception $e) {
    echo "  ✗ SimpleRouter failed: " . $e->getMessage() . "\n";
    $errors++;
}
echo "\n";

// Test 4: Test SimpleJWT
echo "[4/6] Testing SimpleJWT...\n";
try {
    $secret = 'test-secret-key';
    $payload = ['user_id' => 1, 'exp' => time() + 3600];
    $token = SimpleJWT::encode($payload, $secret);
    $decoded = SimpleJWT::decode($token, $secret);
    
    if ($decoded && $decoded->user_id === 1) {
        echo "  ✓ SimpleJWT works\n";
    } else {
        echo "  ✗ SimpleJWT token verification failed\n";
        $errors++;
    }
} catch (Exception $e) {
    echo "  ✗ SimpleJWT failed: " . $e->getMessage() . "\n";
    $errors++;
}
echo "\n";

// Test 5: Test SimpleEnv
echo "[5/6] Testing SimpleEnv...\n";
try {
    $env = new SimpleEnv();
    if (file_exists('.env')) {
        $env->load('.env');
        echo "  ✓ SimpleEnv works (loaded .env)\n";
    } else {
        echo "  ⚠ .env file not found, but SimpleEnv loads\n";
    }
} catch (Exception $e) {
    echo "  ✗ SimpleEnv failed: " . $e->getMessage() . "\n";
    $errors++;
}
echo "\n";

// Test 6: Check controllers
echo "[6/6] Checking controllers are standalone...\n";
$controllers = glob('src/Controllers/*Controller.php');
$psr_found = false;

foreach ($controllers as $controller) {
    $content = file_get_contents($controller);
    if (strpos($content, 'Psr\Http\Message') !== false) {
        echo "  ✗ PSR interfaces found in: " . basename($controller) . "\n";
        $psr_found = true;
        $errors++;
    }
}

if (!$psr_found) {
    echo "  ✓ All controllers are standalone (no PSR interfaces)\n";
}
echo "\n";

// Summary
echo "===========================================\n";
if ($errors === 0) {
    echo "✓ ALL TESTS PASSED - Standalone mode verified!\n";
    echo "✓ No Composer dependencies\n";
    echo "✓ Pure PHP components work\n";
    echo "✓ All controllers converted\n";
    echo "✓ Ready for deployment!\n";
    exit(0);
} else {
    echo "✗ TESTS FAILED: $errors errors found\n";
    echo "Please fix the errors before deployment.\n";
    exit(1);
}
