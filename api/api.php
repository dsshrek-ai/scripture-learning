<?php
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Never let a raw PHP error/notice leak through as HTML — every response
// this API sends must be JSON.
ini_set('display_errors', '0');
set_exception_handler(function ($e) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
  exit;
});

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

function respond($data, int $status = 200): void {
  http_response_code($status);
  echo json_encode($data);
  exit;
}

function fail(string $message, int $status = 400): void {
  respond(['ok' => false, 'error' => $message], $status);
}

function db(): mysqli {
  static $conn = null;
  if ($conn === null) {
    try {
      $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
      $conn->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
      fail('Database connection failed', 500);
    }
  }
  return $conn;
}

function jsonBody(): array {
  $raw = file_get_contents('php://input');
  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : [];
}

// ---- Auth (every action in this app requires login — there's no public,
// anonymous content the way the choir app has; scripture progress is
// inherently personal) ----

function requireUser(): array {
  $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
    fail('Missing or invalid Authorization header', 401);
  }
  $token = $m[1];

  $stmt = db()->prepare(
    'SELECT u.id, u.username, u.display_name
     FROM sessions s JOIN users u ON u.id = s.user_id
     WHERE s.token = ? AND s.expires_at > NOW()'
  );
  $stmt->bind_param('s', $token);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$result) {
    fail('Session expired or invalid — please log in again', 401);
  }
  return $result;
}

function requireAppAccess(array $user): void {
  $stmt = db()->prepare(
    'SELECT 1 FROM app_access aa JOIN apps a ON a.id = aa.app_id
     WHERE aa.user_id = ? AND a.app_key = ?'
  );
  $appKey = 'scripture-learning';
  $stmt->bind_param('is', $user['id'], $appKey);
  $stmt->execute();
  $ok = $stmt->get_result()->fetch_row();
  $stmt->close();
  if (!$ok) {
    fail('Not authorized for Scripture Learning', 403);
  }
}

// Every request needs a logged-in, authorized user — resolve it once up
// front rather than repeating requireUser()+requireAppAccess() in every case.
function currentUser(): array {
  $user = requireUser();
  requireAppAccess($user);
  return $user;
}

// ---- Scripture rows ----

function shapeScripture(array $row): array {
  return [
    'id' => (int)$row['id'],
    'reference' => $row['reference'],
    'book' => $row['book'],
    'scriptureText' => $row['scripture_text'],
    'isActive' => (bool)$row['is_active'],
    'isMemorized' => (bool)$row['is_memorized'],
    'dateMemorized' => $row['date_memorized'],
    'createdAt' => $row['created_at'],
  ];
}

// ---- Router ----

$method = $_SERVER['REQUEST_METHOD'];
$body = $method === 'POST' ? jsonBody() : [];
$action = $method === 'GET' ? ($_GET['action'] ?? '') : ($body['action'] ?? '');

switch ($action) {

  case 'login': {
    $username = trim((string)($body['username'] ?? ''));
    $password = (string)($body['password'] ?? '');
    if ($username === '' || $password === '') {
      fail('Username and password are required');
    }
    $stmt = db()->prepare('SELECT id, password_hash, display_name FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user || !$user['password_hash'] || !password_verify($password, $user['password_hash'])) {
      fail('Invalid username or password', 401);
    }
    $token = bin2hex(random_bytes(32));
    $days = SESSION_LIFETIME_DAYS;
    $ins = db()->prepare('INSERT INTO sessions (token, user_id, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))');
    $ins->bind_param('sii', $token, $user['id'], $days);
    $ins->execute();
    $ins->close();
    respond(['token' => $token, 'displayName' => $user['display_name']]);
  }

  case 'logout': {
    $token = (string)($body['token'] ?? '');
    if ($token !== '') {
      $stmt = db()->prepare('DELETE FROM sessions WHERE token = ?');
      $stmt->bind_param('s', $token);
      $stmt->execute();
      $stmt->close();
    }
    respond(['ok' => true]);
  }

  case 'checkAccess': {
    $user = currentUser();
    respond(['ok' => true, 'displayName' => $user['display_name']]);
  }

  // -- Scripture library (all scoped to the logged-in user) --

  case 'listScriptures': {
    $user = currentUser();
    $filter = (string)($_GET['filter'] ?? 'all');
    $where = 'user_id = ?';
    if ($filter === 'learning') {
      $where .= ' AND is_active = 1 AND is_memorized = 0';
    } elseif ($filter === 'memorized') {
      $where .= ' AND is_memorized = 1';
    } elseif ($filter === 'available') {
      $where .= ' AND is_active = 0 AND is_memorized = 0';
    }
    $stmt = db()->prepare("SELECT * FROM scripture_items WHERE $where ORDER BY created_at DESC");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    respond(['ok' => true, 'scriptures' => array_map('shapeScripture', $rows)]);
  }

  case 'addScripture': {
    $user = currentUser();
    $reference = trim((string)($body['reference'] ?? ''));
    $book = trim((string)($body['book'] ?? ''));
    $scriptureText = trim((string)($body['scriptureText'] ?? ''));
    if ($reference === '' || $book === '' || $scriptureText === '') {
      fail('Reference, book, and scripture text are all required');
    }
    $stmt = db()->prepare(
      'INSERT INTO scripture_items (user_id, reference, book, scripture_text) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('isss', $user['id'], $reference, $book, $scriptureText);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    respond(['ok' => true, 'id' => $id]);
  }

  case 'updateScripture': {
    $user = currentUser();
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) {
      fail('A valid id is required');
    }

    $sets = [];
    $types = '';
    $values = [];

    if (array_key_exists('reference', $body)) {
      $sets[] = 'reference = ?'; $types .= 's'; $values[] = trim((string)$body['reference']);
    }
    if (array_key_exists('book', $body)) {
      $sets[] = 'book = ?'; $types .= 's'; $values[] = trim((string)$body['book']);
    }
    if (array_key_exists('scriptureText', $body)) {
      $sets[] = 'scripture_text = ?'; $types .= 's'; $values[] = trim((string)$body['scriptureText']);
    }
    if (array_key_exists('isActive', $body)) {
      $sets[] = 'is_active = ?'; $types .= 'i'; $values[] = (bool)$body['isActive'] ? 1 : 0;
    }
    if (array_key_exists('isMemorized', $body)) {
      $isMemorized = (bool)$body['isMemorized'];
      $sets[] = 'is_memorized = ?'; $types .= 'i'; $values[] = $isMemorized ? 1 : 0;
      // Stamp (or clear) date_memorized to match, rather than requiring the
      // caller to send both fields in sync.
      $sets[] = 'date_memorized = ?';
      $types .= 's';
      $values[] = $isMemorized ? date('Y-m-d') : null;
    }

    if (!$sets) {
      fail('No fields to update');
    }

    $types .= 'ii';
    $values[] = $id;
    $values[] = $user['id'];
    // user_id in the WHERE, not just id, so a user can never edit another
    // user's scripture by guessing/incrementing an id.
    $sql = 'UPDATE scripture_items SET ' . implode(', ', $sets) . ' WHERE id = ? AND user_id = ?';
    $stmt = db()->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $stmt->close();
    respond(['ok' => true]);
  }

  case 'deleteScripture': {
    $user = currentUser();
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) {
      fail('A valid id is required');
    }
    $stmt = db()->prepare('DELETE FROM scripture_items WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $user['id']);
    $stmt->execute();
    $stmt->close();
    respond(['ok' => true]);
  }

  default:
    respond(['ok' => false, 'error' => 'Unknown action: ' . $action], 404);
}
