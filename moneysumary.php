<?php
// reportmoney.php – สรุปการชำระเงินของนักเรียน (เทียบกับ GET /reportmoney)

require_once 'db.php';

// ─── ดึงข้อมูลทั้งหมดด้วย LEFT JOIN ───
$sql = "
    SELECT r.*, m.id AS is_paid
    FROM stdreport r
    LEFT JOIN stdmoney m ON r.id = m.id
    ORDER BY r.room ASC, r.id ASC
";

$result = $db->query($sql);

if (!$result) {
    die("เกิดข้อผิดพลาด: " . $db->error);
}

// ─── จัดกลุ่มข้อมูลตาม room ───
$groupedData = [];

while ($student = $result->fetch_assoc()) {
    $room = $student['room'];
    if (!isset($groupedData[$room])) {
        $groupedData[$room] = ['paid' => [], 'notPaid' => []];
    }
    if ($student['is_paid']) {
        $groupedData[$room]['paid'][] = $student;
    } else {
        $groupedData[$room]['notPaid'][] = $student;
    }
}

$db->close();

// ─── วันที่ปัจจุบัน ───
$now = new DateTime('now', new DateTimeZone('Asia/Bangkok'));
$buddhistYear = (int)$now->format('Y') + 543;
$thaiDate = $now->format('j') . ' ' . [
    1=>'มกราคม',2=>'กุมภาพันธ์',3=>'มีนาคม',4=>'เมษายน',
    5=>'พฤษภาคม',6=>'มิถุนายน',7=>'กรกฎาคม',8=>'สิงหาคม',
    9=>'กันยายน',10=>'ตุลาคม',11=>'พฤศจิกายน',12=>'ธันวาคม'
][(int)$now->format('n')] . ' ' . $buddhistYear;

// ─── ฟังก์ชันช่วย ───
function e(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function getRoomIcon(string $room): string {
    return match ($room) {
        'm1'  => '📚',
        'm41' => '🔬',
        'm42' => '💻',
        default => '🔧',
    };
}

function getRoomTitle(string $room): string {
    return match ($room) {
        'm1'  => 'แผนการเรียนทั่วไป',
        'm41' => 'แผนวิทยาศาสตร์-คณิตศาสตร์',
        'm42' => 'แผนคณิตศาสตร์-วิทยาศาสตร์และเทคโนโลยี',
        default => 'แผนช่างทั่วไป',
    };
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุปการชำระเงินนักเรียนตามแผนการเรียน</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&family=Noto+Serif+Thai:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a2744; --accent: #c9a84c; --accent-light: #f0d98a;
            --success: #1a6b45; --success-bg: #e8f5ef; --success-border: #6fcf97;
            --danger: #8b1a1a; --danger-bg: #fdf0f0; --danger-border: #eb5757;
            --surface: #ffffff; --surface-2: #f7f6f2; --border: #e2ddd4;
            --text: #1a2744; --text-muted: #6b7280;
            --shadow: 0 4px 24px rgba(26,39,68,0.08);
            --shadow-lg: 0 12px 48px rgba(26,39,68,0.14);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Sarabun', sans-serif;
            background: var(--surface-2);
            color: var(--text);
            min-height: 100vh;
            background-image:
                radial-gradient(ellipse at 0% 0%, rgba(201,168,76,0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 100% 100%, rgba(26,39,68,0.06) 0%, transparent 60%);
        }

        .page-header {
            background: var(--primary); padding: 0;
            position: relative; overflow: hidden;
        }

        .page-header::before {
            content: ''; position: absolute; inset: 0;
            background: repeating-linear-gradient(45deg, transparent, transparent 40px,
                rgba(255,255,255,0.015) 40px, rgba(255,255,255,0.015) 80px);
        }

        .page-header::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, var(--accent) 0%, var(--accent-light) 50%, var(--accent) 100%);
        }

        .header-inner {
            max-width: 1200px; margin: 0 auto; padding: 40px 48px 36px;
            position: relative; display: flex; align-items: center; gap: 24px;
        }

        .header-emblem {
            width: 64px; height: 64px; border: 2px solid var(--accent);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; background: rgba(201,168,76,0.1);
        }

        .header-emblem svg { width: 32px; height: 32px; fill: none; stroke: var(--accent); stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

        .header-text h1 {
            font-family: 'Noto Serif Thai', serif; font-size: 1.75rem; font-weight: 700;
            color: #ffffff; letter-spacing: 0.02em; line-height: 1.3;
        }

        .header-text p { color: rgba(255,255,255,0.55); font-size: 0.85rem; margin-top: 4px; letter-spacing: 0.05em; text-transform: uppercase; }

        .header-badge {
            margin-left: auto; background: rgba(201,168,76,0.15);
            border: 1px solid rgba(201,168,76,0.4); border-radius: 8px;
            padding: 10px 20px; text-align: center; flex-shrink: 0;
        }

        .header-badge .badge-label { font-size: 0.7rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.1em; display: block; }
        .header-badge .badge-value { font-size: 1.5rem; font-weight: 700; color: var(--accent); display: block; line-height: 1.2; }

        .page-body { max-width: 1200px; margin: 0 auto; padding: 48px 48px; }

        .room-section {
            background: var(--surface); border-radius: 16px; box-shadow: var(--shadow);
            margin-bottom: 32px; overflow: hidden; border: 1px solid var(--border);
            animation: fadeSlideIn 0.5s ease both;
        }

        .room-section:nth-child(1) { animation-delay: 0.05s; }
        .room-section:nth-child(2) { animation-delay: 0.10s; }
        .room-section:nth-child(3) { animation-delay: 0.15s; }
        .room-section:nth-child(4) { animation-delay: 0.20s; }

        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .room-header {
            background: var(--primary); padding: 20px 28px;
            display: flex; align-items: center; gap: 16px;
            position: relative; overflow: hidden;
        }

        .room-header::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0;
            height: 2px; background: linear-gradient(90deg, var(--accent), transparent);
        }

        .room-icon {
            width: 40px; height: 40px; background: rgba(201,168,76,0.15);
            border: 1px solid rgba(201,168,76,0.35); border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
        }

        .room-title { font-family: 'Noto Serif Thai', serif; font-size: 1.1rem; font-weight: 600; color: #fff; }
        .room-plan-tag { font-size: 0.72rem; color: var(--accent); letter-spacing: 0.08em; text-transform: uppercase; margin-top: 2px; }

        .room-stats { margin-left: auto; display: flex; gap: 12px; align-items: center; }

        .stat-pill {
            padding: 5px 14px; border-radius: 20px; font-size: 0.8rem;
            font-weight: 600; display: flex; align-items: center; gap: 6px;
        }

        .stat-pill.success { background: rgba(26,107,69,0.25); color: #6fcf97; border: 1px solid rgba(111,207,151,0.3); }
        .stat-pill.danger  { background: rgba(139,26,26,0.25); color: #f87171; border: 1px solid rgba(248,113,113,0.3); }

        .table-container { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }

        .table-panel { padding: 24px 28px; }
        .table-panel:first-child { border-right: 1px solid var(--border); }

        .panel-header {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid var(--border);
        }

        .panel-indicator { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .panel-indicator.success { background: var(--success-border); box-shadow: 0 0 0 3px var(--success-bg); }
        .panel-indicator.danger  { background: var(--danger-border);  box-shadow: 0 0 0 3px var(--danger-bg); }

        .panel-title { font-size: 0.95rem; font-weight: 600; }
        .panel-title.success { color: var(--success); }
        .panel-title.danger  { color: var(--danger); }

        .panel-count {
            margin-left: auto; font-size: 0.78rem; font-weight: 700;
            padding: 3px 10px; border-radius: 12px;
        }

        .panel-count.success { background: var(--success-bg); color: var(--success); border: 1px solid var(--success-border); }
        .panel-count.danger  { background: var(--danger-bg);  color: var(--danger);  border: 1px solid var(--danger-border); }

        table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.875rem; }

        thead th {
            padding: 10px 14px; text-align: left; font-size: 0.72rem; font-weight: 700;
            letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted);
            background: var(--surface-2); border-bottom: 1px solid var(--border);
        }

        thead th:first-child { border-radius: 8px 0 0 0; }
        thead th:last-child  { border-radius: 0 8px 0 0; }

        tbody tr { transition: background 0.15s; }
        tbody tr:hover { background: var(--surface-2); }
        tbody td { padding: 10px 14px; border-bottom: 1px solid var(--border); color: var(--text); line-height: 1.5; }
        tbody tr:last-child td { border-bottom: none; }

        .id-cell { font-family: 'Sarabun', monospace; font-size: 0.8rem; color: var(--text-muted); width: 110px; }
        .name-cell { font-weight: 500; }
        .success-row:hover { background: var(--success-bg) !important; }
        .danger-row:hover  { background: var(--danger-bg) !important; }

        .empty-state { text-align: center; padding: 32px 16px; color: var(--text-muted); font-size: 0.875rem; }
        .empty-icon { font-size: 1.5rem; margin-bottom: 8px; }

        .page-footer {
            text-align: center; padding: 32px 48px; color: var(--text-muted);
            font-size: 0.78rem; letter-spacing: 0.04em;
            border-top: 1px solid var(--border); margin-top: 16px;
        }

        @media (max-width: 768px) {
            .header-inner  { padding: 28px 24px; flex-wrap: wrap; }
            .page-body     { padding: 24px 16px; }
            .table-container { grid-template-columns: 1fr; }
            .table-panel:first-child { border-right: none; border-bottom: 1px solid var(--border); }
            .room-stats    { display: none; }
        }

        @media print {
            body { background: white; }
            .room-section { box-shadow: none; border: 1px solid #ccc; }
            .page-header  { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .room-header  { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<!-- PAGE HEADER -->
<header class="page-header">
    <div class="header-inner">
        <div class="header-emblem">
            <!-- ไอคอนเหรียญ/เงิน -->
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 6v12M9 9.5c0-1.1.9-2 2-2h2a2 2 0 0 1 0 4h-2a2 2 0 0 0 0 4h2a2 2 0 0 0 2-2"/>
            </svg>
        </div>
        <div class="header-text">
            <h1>รายงานสรุปการชำระเงินนักเรียน – โรงเรียนอรพิมพ์วิทยา</h1>
            <p>สรุปการชำระเงิน &nbsp;·&nbsp; แยกตามแผนการเรียน</p>
        </div>
        <div class="header-badge">
            <span class="badge-label">ปีการศึกษา</span>
            <span class="badge-value"><?= $buddhistYear ?></span>
        </div>
    </div>
</header>

<!-- MAIN BODY -->
<main class="page-body">

    <?php foreach ($groupedData as $room => $data): ?>
    <section class="room-section">

        <div class="room-header">
            <div class="room-icon"><?= getRoomIcon($room) ?></div>
            <div>
                <div class="room-title"><?= e(getRoomTitle($room)) ?></div>
                <div class="room-plan-tag"><?= e($room) ?></div>
            </div>
            <div class="room-stats">
                <div class="stat-pill success">✓ &nbsp;<?= count($data['paid']) ?> คน</div>
                <div class="stat-pill danger">✗ &nbsp;<?= count($data['notPaid']) ?> คน</div>
            </div>
        </div>

        <div class="table-container">

            <!-- จ่ายเงินแล้ว -->
            <div class="table-panel">
                <div class="panel-header">
                    <div class="panel-indicator success"></div>
                    <span class="panel-title success">จ่ายเงินแล้ว</span>
                    <span class="panel-count success"><?= count($data['paid']) ?> คน</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th class="id-cell">เลขประจำตัว</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>สถานะการจ่าย</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($data['paid']) === 0): ?>
                        <tr><td colspan="3">
                            <div class="empty-state">
                                <div class="empty-icon">–</div>
                                ไม่มีรายการ
                            </div>
                        </td></tr>
                        <?php else: ?>
                            <?php foreach ($data['paid'] as $st): ?>
                            <tr class="success-row">
                                <td class="id-cell"><?= e($st['id']) ?></td>
                                <td class="name-cell"><?= e($st['name']) ?> </td>
                                <td class="name-cell"><?= e($st['state']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ยังไม่จ่ายเงิน -->
            <div class="table-panel">
                <div class="panel-header">
                    <div class="panel-indicator danger"></div>
                    <span class="panel-title danger">ยังไม่จ่ายเงิน</span>
                    <span class="panel-count danger"><?= count($data['notPaid']) ?> คน</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th class="id-cell">เลขประจำตัว</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($data['notPaid']) === 0): ?>
                        <tr><td colspan="3">
                            <div class="empty-state">
                                <div class="empty-icon">🎉</div>
                                ชำระเงินครบทุกคนแล้ว
                            </div>
                        </td></tr>
                        <?php else: ?>
                            <?php foreach ($data['notPaid'] as $st): ?>
                            <tr class="danger-row">
                                <td class="id-cell"><?= e($st['id']) ?></td>
                                <td class="name-cell"><?= e($st['name']) ?></td>
                                <td class="name-cell"></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </section>
    <?php endforeach; ?>

</main>

<footer class="page-footer">
    จัดทำโดยระบบสารสนเทศโรงเรียน &nbsp;·&nbsp; พิมพ์เมื่อ <?= $thaiDate ?>
</footer>

</body>
</html>