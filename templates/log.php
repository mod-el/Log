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

    .logs-row-date {
        width: 20%;
    }

    .logs-row-url {
        width: 25%;
    }

    .logs-row-get {
        width: 25%;
    }

    .logs-row-user {
        width: 15%;
    }

    .logs-row-reason {
        width: 15%;
    }
</style>

<h2>Logs</h2>

<div>
    <div class="logs-row logs-headings">
        <div class="logs-row-date">Data</div>
        <div class="logs-row-url">Url</div>
        <div class="logs-row-get">Get</div>
        <div class="logs-row-user">User</div>
        <div class="logs-row-reason">Reason</div>
    </div>
	<?php
	$logs = $this->model->_Log->getLogs();
	foreach ($logs as $l) {
		?>
        <a class="clickable logs-row" href="<?= PATH ?>zk/modules/config/Log/<?= $l['id'] ?>">
            <span class="logs-row-date">
				<?= date_create($l['date'])->format('d/m/Y H:i:s') ?>
            </span>
            <span class="logs-row-url">
                <?= entities($l['url']) ?>
            </span>
            <span class="logs-row-get">
                <?= entities($l['get']) ?>
            </span>
            <span class="logs-row-user">
                <?= entities(substr($l['user'], 0, 12)) ?>
            </span>
            <span class="logs-row-reason">
                <?= entities($l['reason']) ?>
            </span>
        </a>
		<?php
	}
	?>
</div>
