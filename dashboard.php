<?php
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/session.php';
}

require_once('database.php');

if (!isset($_SESSION['userID'])) {
    header('Location: login.php');
    exit();
}

$fullName = $_SESSION['fullName'] ?? 'Parent';
if (empty($_SESSION['csrfToken'])) {
    $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
}

$query = 'SELECT activities.activityID,
                 activities.activityName,
                 activities.activityDate,
                 activities.activityTime,
                 activities.notes,
                 children.childName
          FROM activities
          INNER JOIN children
              ON activities.childID = children.childID
          WHERE children.userID = :userID
          ORDER BY activities.activityDate,
                   activities.activityTime';

$statement = $db->prepare($query);
$statement->bindValue(':userID', $_SESSION['userID'], PDO::PARAM_INT);
$statement->execute();
$activities = $statement->fetchAll(PDO::FETCH_ASSOC);
$statement->closeCursor();

$query = 'SELECT reminders.reminderID,
                 reminders.reminderDateTime,
                 activities.activityName,
                 children.childName
          FROM reminders
          INNER JOIN activities
              ON reminders.activityID = activities.activityID
          INNER JOIN children
              ON activities.childID = children.childID
          WHERE children.userID = :userID
            AND reminders.isCompleted = 0
          ORDER BY reminders.reminderDateTime';

$statement = $db->prepare($query);
$statement->bindValue(':userID', $_SESSION['userID'], PDO::PARAM_INT);
$statement->execute();
$reminders = $statement->fetchAll(PDO::FETCH_ASSOC);
$statement->closeCursor();

/* Calendar uses the current month in Toronto. */
$timezone = new DateTimeZone('America/Toronto');
$today = new DateTimeImmutable('today', $timezone);
$monthStart = $today->modify('first day of this month');
$daysInMonth = (int) $monthStart->format('t');
$startingWeekday = (int) $monthStart->format('w');
$monthKey = $monthStart->format('Y-m');
$activityDays = [];

foreach ($activities as $activity) {
    $activityDate = $activity['activityDate'];

    if (substr($activityDate, 0, 7) === $monthKey) {
        $day = (int) substr($activityDate, 8, 2);
        $activityDays[$day] = true;
    }
}

$calendarCells = (int) (
    ceil(($startingWeekday + $daysInMonth) / 7) * 7
);

function escapeHtml($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - LittleSteps</title>
    <link rel="stylesheet" href="CSS/style.css?v=2">
</head>

<body class="dashboard-page">
    <header>
        <h1>LittleSteps</h1>

        <div class="dashboard-account">
            <span class="profile-badge" aria-hidden="true">
                <?php echo escapeHtml(
                    mb_substr($fullName, 0, 1, 'UTF-8')
                ); ?>
            </span>
            <a href="logout.php">Log Out</a>
        </div>
    </header>

    <main>
        <div class="dashboard-welcome">
            <div>
                <h2>Welcome, <?php echo escapeHtml($fullName); ?>!</h2>
                <p>Here’s what’s happening with your family.</p>
            </div>

            <a class="dashboard-primary" href="add_activity.php">
                + Add Activity
            </a>
        </div>

        <nav class="dashboard-nav" aria-label="Family management">
            <a href="add_child.php">+ Add Child</a>
            <a href="manage_children.php">Manage Children</a>
            <a href="add_reminder.php">+ Add Reminder</a>
            <a href="calendar.php">Calendar</a>
        </nav>

        <div class="dashboard-layout">
            <div class="dashboard-content">
                <section aria-labelledby="activities-heading">
                    <div class="section-heading">
                        <h3 id="activities-heading">Family Activities</h3>
                        <span class="dashboard-count">
                            <?php echo count($activities); ?> total
                        </span>
                    </div>

                    <?php if (count($activities) === 0): ?>
                        <article class="dashboard-empty">
                            <h4>Your family’s plans start here</h4>
                            <p>Add a child, then create your first activity.</p>
                            <a href="add_child.php">Add a child →</a>
                        </article>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <?php
                            $date = new DateTimeImmutable(
                                $activity['activityDate'],
                                $timezone
                            );
                            ?>
                            <article class="dashboard-activity">
                                <div class="activity-card-top">
                                    <div>
                                        <h4>
                                            <?php echo escapeHtml(
                                                $activity['activityName']
                                            ); ?>
                                        </h4>

                                        <p class="activity-meta">
                                            <?php echo escapeHtml(
                                                $activity['childName']
                                            ); ?>
                                            •
                                            <?php echo escapeHtml(
                                                $date->format('M j, Y')
                                            ); ?>
                                            •
                                            <?php echo escapeHtml(
                                                date(
                                                    'g:i A',
                                                    strtotime(
                                                        $activity['activityTime']
                                                    )
                                                )
                                            ); ?>
                                        </p>
                                    </div>

                                    <time
                                        class="activity-date-badge"
                                        datetime="<?php echo escapeHtml(
                                            $activity['activityDate']
                                        ); ?>"
                                    >
                                        <span>
                                            <?php echo escapeHtml(
                                                strtoupper($date->format('M'))
                                            ); ?>
                                        </span>
                                        <strong>
                                            <?php echo $date->format('d'); ?>
                                        </strong>
                                    </time>
                                </div>

                                <?php if (
                                    trim((string) $activity['notes']) !== ''
                                ): ?>
                                    <p class="activity-notes">
                                        <?php echo nl2br(
                                            escapeHtml($activity['notes'])
                                        ); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="activity-card-actions">
                                    <a href="edit_activity.php?id=<?php
                                        echo (int) $activity['activityID'];
                                    ?>">Edit</a>

                                    <form
                                        action="delete_activity.php"
                                        method="post"
                                        onsubmit="return confirm(
                                            'Delete this activity?'
                                        );"
                                    >
                                        <input
                                            type="hidden"
                                            name="activityID"
                                            value="<?php
                                                echo (int) $activity['activityID'];
                                            ?>"
                                        >
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

                <section
                    class="dashboard-reminders"
                    aria-labelledby="reminders-heading"
                >
                    <h3 id="reminders-heading">Reminders</h3>

                    <?php if (count($reminders) === 0): ?>
                        <article class="dashboard-empty">
                            <p>No pending reminders.</p>
                            <a href="add_reminder.php">+ Add Reminder</a>
                        </article>
                    <?php else: ?>
                        <?php foreach ($reminders as $reminder): ?>
                            <article class="dashboard-reminder">
                                <h4>
                                    <?php echo escapeHtml(
                                        $reminder['activityName']
                                    ); ?>
                                </h4>

                                <p>
                                    <?php echo escapeHtml(
                                        $reminder['childName']
                                    ); ?>
                                    •
                                    <?php echo escapeHtml(
                                        date(
                                            'M j, Y — g:i A',
                                            strtotime(
                                                $reminder['reminderDateTime']
                                            )
                                        )
                                    ); ?>
                                </p>

                                <span class="reminder-status">Pending</span>
                                <form action="complete_reminder.php" method="post">
    <input
        type="hidden"
        name="reminderID"
        value="<?php echo (int) $reminder['reminderID']; ?>"
    >

    <input
        type="hidden"
        name="csrfToken"
        value="<?php echo escapeHtml($_SESSION['csrfToken']); ?>"
    >

    <button type="submit">Mark as Completed</button>
</form>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="dashboard-sidebar">
                <section class="calendar-panel">
                    <h3>
                        <?php echo escapeHtml(
                            $monthStart->format('F Y')
                        ); ?>
                    </h3>

                    <div class="mini-calendar">
                        <?php foreach (
                            ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
                            as $weekday
                        ): ?>
                            <span class="calendar-weekday">
                                <?php echo $weekday; ?>
                            </span>
                        <?php endforeach; ?>

                        <?php for (
                            $cell = 0;
                            $cell < $calendarCells;
                            $cell++
                        ): ?>
                            <?php
                            $day = $cell - $startingWeekday + 1;
                            ?>

                            <?php if ($day < 1 || $day > $daysInMonth): ?>
                                <span aria-hidden="true"></span>
                            <?php else: ?>
                                <?php
                                $isToday = $day === (int) $today->format('j');
                                $hasActivity = isset($activityDays[$day]);
                                $classes = 'calendar-day';

                                if ($isToday) {
                                    $classes .= ' is-today';
                                }

                                if ($hasActivity) {
                                    $classes .= ' has-activity';
                                }

                                $label = $monthStart->setDate(
                                    (int) $monthStart->format('Y'),
                                    (int) $monthStart->format('m'),
                                    $day
                                )->format('F j, Y');

                                if ($hasActivity) {
                                    $label .= ', activity scheduled';
                                }
                                ?>
                                <span
                                    class="<?php echo $classes; ?>"
                                    <?php if ($isToday): ?>
                                        aria-current="date"
                                    <?php endif; ?>
                                    aria-label="<?php echo escapeHtml($label); ?>"
                                >
                                    <?php echo $day; ?>
                                </span>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>

                    <p class="calendar-legend">
                        <span aria-hidden="true"></span>
                        Activity scheduled
                    </p>

                    <a class="calendar-open" href="calendar.php">
                        Open full calendar →
                    </a>
                </section>
            </aside>
        </div>

        <footer class="dashboard-footer">
            LittleSteps · Plan today. Smile tomorrow.
        </footer>
    </main>
</body>
</html>