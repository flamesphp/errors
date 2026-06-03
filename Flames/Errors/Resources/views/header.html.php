<div class="exception">
  <div class="exception-top">
    <div class="exception-top-main">
      <div class="exc-title">
        <?php foreach ($name as $i => $nameSection): ?>
          <?php if ($i == count($name) - 1): ?>
            <span class="exc-title-primary"><?php echo $tpl->escape($nameSection) ?></span>
          <?php else: ?>
            <?php echo $tpl->escape($nameSection) . ' \\' ?>
          <?php endif ?>
        <?php endforeach ?>
        <?php if ($code): ?>
          <span title="Exception Code">(<?php echo $tpl->escape($code) ?>)</span>
        <?php endif ?>
      </div>
    </div>

    <div class="exception-actions">
      <?php if (!empty($docref_url)): ?>
        <a class="exception-action exception-action-icon" rel="noopener noreferrer" target="_blank" href="<?php echo $docref_url; ?>" title="Search for help in the PHP manual." aria-label="PHP manual">
          <svg height="16" width="16" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M29 4v26H5c-1.104 0-2-.895-2-2s.896-2 2-2h22V0H5C2.791 0 1 1.791 1 4v24c0 2.209 1.791 4 4 4h26V4H29zM7 8V2h20v22H7V8zm16-2h-12V4h12V6zm-4 4h-8V8h8v2zm-4 4h-4v-2h4v2z"/></svg>
        </a>
      <?php endif ?>
      <a class="exception-action exception-action-icon search-on-web" rel="noopener noreferrer" target="_blank" href="https://google.com/search?q=<?php echo urlencode(implode('\\', $name).' '.$message) ?>" title="Search for help on the web." aria-label="Search on web">
        <svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
      </a>
      <button id="copy-button" type="button" class="exception-action exception-action-icon rightButton clipboard" data-clipboard-text="<?php echo $tpl->escape($plain_exception) ?>" title="Copy exception details to clipboard" aria-label="Copy">
        <svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
      </button>
      <button id="reload-button" type="button" class="exception-action exception-action-icon" title="Reload page" aria-label="Reload">
        <svg width="16" height="16" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M17.65 6.35A7.958 7.958 0 0 0 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0 1 12 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
      </button>
    </div>
  </div>

  <div class="exc-message">
    <?php if (!empty($message)): ?>
      <span><?php echo $tpl->escape($message) ?></span>

      <?php if (count($previousMessages)): ?>
        <div class="exc-title prev-exc-title">
          <span class="exc-title-secondary">Previous exceptions</span>
        </div>

        <ul>
          <?php foreach ($previousMessages as $i => $previousMessage): ?>
            <li>
              <?php echo $tpl->escape($previousMessage) ?>
              <span class="prev-exc-code">(<?php echo $previousCodes[$i] ?>)</span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif ?>
    <?php else: ?>
      <span class="exc-message-empty-notice">No message</span>
    <?php endif ?>
  </div>

  <span id="plain-exception"><?php echo $tpl->escape($plain_exception) ?></span>
</div>
