<?php /* List file names & line numbers for all stack frames;
         clicking these links/buttons will display the code view
         for that particular frame */ ?>
<?php foreach ($frames as $i => $frame): ?>
  <div class="frame <?php echo ($i == 0 ? 'active' : '') ?> <?php echo ($frame->isApplication() ? 'frame-application' : '') ?>" id="frame-line-<?php echo $i ?>">
      <span class="frame-index"><?php echo (count($frames) - $i - 1) ?></span>
      <div class="frame-method-info">
        <?php
          $frameClass = $frame->getClass() ?: '';
          $frameFunction = $frame->getFunction() ?: '';
        ?>
        <?php if ($frameClass !== '' && $frameFunction !== ''): ?>
        <span class="frame-class"><?php echo $tpl->breakOnDelimiter('\\', $tpl->escape($frameClass)) ?></span><span class="frame-separator">::</span><span class="frame-function"><?php echo $tpl->breakOnDelimiter('\\', $tpl->escape($frameFunction)) ?></span>
        <?php elseif ($frameClass !== ''): ?>
        <span class="frame-class"><?php echo $tpl->breakOnDelimiter('\\', $tpl->escape($frameClass)) ?></span>
        <?php elseif ($frameFunction !== ''): ?>
        <span class="frame-function"><?php echo $tpl->breakOnDelimiter('\\', $tpl->escape($frameFunction)) ?></span>
        <?php endif ?>
      </div>

    <div class="frame-file">
        <?php if ($frame->getFile()): ?>
        <?php
          $frameFile = $frame->getFile();
          $frameLine = (int) $frame->getLine();
          $frameLabel = $tpl->frameFileLabel($frameFile, $frameLine);
          $frameLineSuffix = $tpl->frameFileLine($frameFile, $frameLine);
        ?>
        <?php echo $tpl->breakOnDelimiter('/', $tpl->escape($frameLabel)) ?><!--
   --><?php if ($frameLineSuffix !== null): ?><span class="frame-line"><?php echo $frameLineSuffix ?></span><?php endif ?>
        <?php else: ?>
        <#unknown>
        <?php endif ?>
    </div>
  </div>
<?php endforeach;
