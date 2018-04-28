<?php
$logRow = $this->model->_Db->select('zk_log', $this->model->getRequest(4));
if (!$logRow) {
	echo 'Log row not found';
	return;
}
?>
<style>
    .logs-row, a.logs-row {
        font-size: 0;
        display: block;
        color: #333;
    }

    .logs-row > * {
        display: inline-block;
        font-size: 1rem;
        padding: 4px;
        box-sizing: border-box;
        vertical-align: middle;
        -ms-word-wrap: break-word;
        word-wrap: break-word;
    }

    .logs-headings {
        font-weight: bold;
        color: #000;
    }

    .logs-row-module {
        width: 10%;
    }

    .logs-row-event {
        width: 25%;
    }

    .logs-row-data {
        width: 23%;
    }

    .logs-row-time {
        width: 21%;
    }

    .logs-row-offset {
        width: 21%;
    }
</style>

<script>
	function contentLightbox(id) {
		lightbox(document.getElementById(id).innerHTML);
	}
</script>

<h2>Log - <?= date_create($logRow['date'])->format('d/m/Y H:i:s') ?></h2>

<?php
$post = json_decode($logRow['post'], true);
$session = json_decode($logRow['session'], true);
?>

<p>
    <b>Reason:</b> <?= entities($logRow['reason']) ?><br/>
    <b>Expire at:</b> <?= $logRow['expire_at'] ? date_create($logRow['expire_at'])->format('d/m/Y H:i:s') : '<i>never</i>' ?>
    <br/>
    <b>Url:</b> <?= entities($logRow['url']) ?><br/>
    <b>Get:</b> <?= entities($logRow['get']) ?><br/>
    <b>Post:</b> <?= $post ? '[<a href="#" onclick="contentLightbox(\'post-content\'); return false"> show </a>]' : '<i>empty</i>' ?>
    <br/>
    <br/>
    <b>User:</b> <?= entities($logRow['user']) ?><br/>
    <b>User Hash:</b> <?= entities($logRow['user_hash']) ?><br/>
    <b>Session:</b> <?= $session ? '[<a href="#" onclick="contentLightbox(\'session-content\'); return false"> show </a>]' : '<i>empty</i>' ?>
    <br/>
</p>

<div id="post-content" style="display: none;">
    <pre>
	    <?php
		var_dump($post);
		?>
    </pre>
</div>

<div id="session-content" style="display: none;">
    <pre>
	    <?php
		var_dump($session);
		?>
    </pre>
</div>

<?php
$events = json_decode($logRow['events'], true);
if (!$events) {
	echo '<p><i>Events log corrupted</i></p>';
	return;
}
?>

<div>
    <div class="logs-row logs-headings">
        <div class="logs-row-module">Module</div>
        <div class="logs-row-event">Event</div>
        <div class="logs-row-data">Data</div>
        <div class="logs-row-time">Time</div>
        <div class="logs-row-offset">Offset</div>
    </div>
	<?php
	$startTime = null;
	$lastTime = null;
	foreach ($events as $idx => $e) {
		?>
        <div onclick="contentLightbox('data-<?= $idx ?>')" class="clickable logs-row">
            <div class="logs-row-module">
				<?= entities($e['module']) ?>
            </div>
            <div class="logs-row-event">
				<?= entities($e['event']) ?>
            </div>
            <div class="logs-row-data">
				<?php
				$this->model->_Log->showEventData($e['module'], $e['event'], $e['data']);
				?>
            </div>
            <div class="logs-row-time">
				<?php
				if ($startTime === null)
					$startTime = $e['time'];
				echo $e['time'] - $startTime;
				?>
            </div>
            <div class="logs-row-offset">
				<?php
				if ($lastTime !== null) {
					echo $e['time'] - $lastTime;
				}
				$lastTime = $e['time'];
				?>
            </div>
        </div>
        <div id="data-<?= $idx ?>" style="display: none">
            <pre>
                <?php
				var_dump($e['data']);
				?>
            </pre>
        </div>
		<?php
	}
	?>
</div>
