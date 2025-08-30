<?php
// go up one dir from /coordinator to reach /includes
$ROOT = dirname(__DIR__);
require_once $ROOT . '/includes/auth.php';
require_once $ROOT . '/includes/db.php';

if (!isLoggedIn()) {
    header("Location: /index.php");
    exit();
}

$me = (int)$_SESSION['user_id'];

// Build partner list: admin ↔ coordinators; coordinator ↔ admins
if (isAdmin()) {
    $partnersStmt = $pdo->query("SELECT id, username, department FROM users WHERE role = 'coordinator' AND is_active = 1 ORDER BY username");
} else {
    $partnersStmt = $pdo->query("SELECT id, username, department FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY username");
}
$partners = $partnersStmt->fetchAll(PDO::FETCH_ASSOC);

// Pick active partner (?with=ID). Default: first in list.
$with = isset($_GET['with']) ? (int)$_GET['with'] : (int)($partners[0]['id'] ?? 0);

// Validate chosen partner is allowed
$allowedIds = array_column($partners, 'id');
if ($with && !in_array($with, $allowedIds, true)) {
    $with = (int)($partners[0]['id'] ?? 0);
}

// Handle message send
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send']) && $with) {
    $body = trim($_POST['body'] ?? '');
    if ($body === '') {
        $errors[] = "Message cannot be empty.";
    } elseif (mb_strlen($body) > 3000) {
        $errors[] = "Message is too long (max 3000 chars).";
    } else {
        // NOTE: column is `message`, not `body`
        $ins = $pdo->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $ins->execute([$me, $with, $body]);

        // PRG pattern to avoid resubmission
        header("Location: /coordinator/chat.php?with=" . $with);
        exit();
    }
}

// Fetch conversation (latest 200)
$conversation = [];
if ($with) {
    $msgStmt = $pdo->prepare("
        SELECT m.*, u.username AS sender_name
          FROM chat_messages m
          JOIN users u ON u.id = m.sender_id
         WHERE (m.sender_id = ? AND m.receiver_id = ?)
            OR (m.sender_id = ? AND m.receiver_id = ?)
         ORDER BY m.created_at ASC
         LIMIT 200
    ");
    $msgStmt->execute([$me, $with, $with, $me]);
    $conversation = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark incoming as read
    $mark = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
    $mark->execute([$me, $with]);
}

// include header/footer correctly
require_once $ROOT . '/includes/header.php';
?>
<div class="chat-layout">
  <aside class="chat-people">
    <div class="chat-people-header">Conversations</div>
    <?php if (empty($partners)): ?>
      <div class="chat-empty">No available partner.</div>
    <?php else: ?>
      <?php foreach ($partners as $p): ?>
        <?php
          // unread count per partner
          $cStmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
          $cStmt->execute([$me, (int)$p['id']]);
          $unread = (int)$cStmt->fetchColumn();
        ?>
        <a class="chat-person <?= ($with === (int)$p['id']) ? 'active' : '' ?>"
           href="/coordinator/chat.php?with=<?= (int)$p['id'] ?>">
          <div class="avatar"><?= strtoupper(mb_substr($p['username'],0,1)) ?></div>
          <div class="meta">
            <div class="name"><?= htmlspecialchars($p['username']) ?></div>
            <div class="dept"><?= htmlspecialchars($p['department'] ?? '') ?></div>
          </div>
          <?php if ($unread > 0): ?>
            <span class="badge"><?= $unread ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </aside>

  <section class="chat-panel">
    <?php if (!$with): ?>
      <div class="chat-empty big">No partner selected.</div>
    <?php else: ?>
      <div class="chat-header">
        <?php
          $partner = array_values(array_filter($partners, fn($pp) => (int)$pp['id'] === $with))[0] ?? null;
        ?>
        <div>
          <div class="chat-title"><?= htmlspecialchars($partner['username'] ?? 'User') ?></div>
          <div class="chat-sub"><?= htmlspecialchars($partner['department'] ?? '') ?></div>
        </div>
        <form method="get" action="/coordinator/chat.php" class="chat-refresh">
          <input type="hidden" name="with" value="<?= $with ?>">
          <button class="btn btn-small" title="Refresh">Refresh</button>
        </form>
      </div>

      <div class="chat-thread">
        <?php if (empty($conversation)): ?>
          <div class="chat-empty">No messages yet.</div>
        <?php else: ?>
          <?php foreach ($conversation as $m): ?>
            <?php $mine = ((int)$m['sender_id'] === $me); ?>
            <div class="bubble <?= $mine ? 'mine' : '' ?>">
              <div class="bubble-body"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
              <div class="bubble-meta">
                <span><?= date('M d, Y H:i', strtotime($m['created_at'])) ?></span>
                <?php if ($mine): ?>
                  <span> • <?= $m['is_read'] ? 'Read' : 'Sent' ?></span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <form method="post" class="chat-compose">
        <?php if (!empty($errors)): ?>
          <div class="alert alert-error" style="margin:0 0 10px;">
            <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
          </div>
        <?php endif; ?>
        <textarea name="body" rows="2" placeholder="Type your message..." required></textarea>
        <button type="submit" name="send" class="btn">Send</button>
      </form>
    <?php endif; ?>
  </section>
</div>

<style>
/* ====== Chat UI (white + dark blue) ====== */
:root{
  --navy:#192f5d; --navy-700:#14254a;
  --bg:#fff; --bg-soft:#f4f7fb;
  --border:#e5e7eb; --muted:#6b7280;
  --radius:12px; --radius-sm:10px;
}
.chat-layout{ display:grid; grid-template-columns: 320px 1fr; gap:14px; min-height:70vh; }
@media (max-width: 900px){ .chat-layout{ grid-template-columns:1fr; } }

/* People list */
.chat-people{ background:#fff; border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
.chat-people-header{ padding:12px 14px; background:#f3f6fb; color:var(--navy); font-weight:700; border-bottom:1px solid var(--border); }
.chat-person{ display:flex; align-items:center; gap:10px; padding:10px 12px; text-decoration:none; color:inherit; border-bottom:1px solid var(--border); }
.chat-person:hover{ background:#fbfdff; }
.chat-person.active{ background:#eef3ff; }
.chat-person .avatar{ width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg, var(--navy), var(--navy-700)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; }
.chat-person .meta{ flex:1; }
.chat-person .name{ font-weight:700; }
.chat-person .dept{ color:var(--muted); font-size:.85rem; }
.chat-person .badge{ background:#dc2626; color:#fff; border-radius:999px; padding:2px 8px; font-size:.75rem; font-weight:700; }

/* Panel */
.chat-panel{ display:flex; flex-direction:column; background:#fff; border:1px solid var(--border); border-radius:var(--radius); min-height:70vh; }
.chat-header{ display:flex; align-items:center; justify-content:space-between; padding:12px 14px; border-bottom:1px solid var(--border); background:#f8f9fa; }
.chat-title{ font-weight:800; color:var(--navy); }
.chat-sub{ color:var(--muted); font-size:.9rem; }
.chat-thread{ flex:1; padding:16px; overflow:auto; background:var(--bg-soft); }
.chat-empty{ color:var(--muted); padding:16px; }
.chat-empty.big{ padding:40px; text-align:center; }

/* Bubbles */
.bubble{ max-width:70%; margin:8px 0; padding:10px 12px; background:#fff; border:1px solid var(--border); border-radius:14px; }
.bubble .bubble-meta{ color:var(--muted); font-size:.78rem; margin-top:4px; }
.bubble.mine{ margin-left:auto; background:#eaf3ff; border-color:#d6e6ff; }

/* Compose */
.chat-compose{ display:grid; grid-template-columns:1fr 120px; gap:10px; padding:12px; border-top:1px solid var(--border); background:#fff; }
.chat-compose textarea{ width:100%; resize:vertical; border:1px solid var(--border); border-radius:var(--radius-sm); padding:10px 12px; min-height:56px; }
.chat-compose textarea:focus{ outline:none; border-color:var(--navy); box-shadow:0 0 0 3px rgba(25,47,93,.12); }
</style>

<?php require_once $ROOT . '/includes/footer.php'; ?>
