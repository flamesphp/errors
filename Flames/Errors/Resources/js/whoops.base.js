Zepto(function($) {
  var $leftPanel      = $('.left-panel');
  var $frameContainer = $('.frames-container');
  var $appFramesTab   = $('#application-frames-tab');
  var $allFramesTab   = $('#all-frames-tab');
  var $container      = $('.details-container');
  var $activeLine     = $frameContainer.find('.frame.active');
  var $activeFrame    = $container.find('.frame-code.active');
  var $ajaxEditors    = $('.editor-link[data-ajax]');
  var $header         = $('header');

  $header.on('mouseenter', function () {
    if ($header.find('.exception').height() >= 145) {
      $header.addClass('header-expand');
    }
  });
  $header.on('mouseleave', function () {
    $header.removeClass('header-expand');
  });

  /*
   * add prettyprint classes to our current active codeblock
   * run prettyPrint() to highlight the active code
   * scroll to the line when prettyprint is done
   * highlight the current line
   */
  var prepareCodeFrame = function($frame) {
    $activeFrame = $frame;

    if (!$frame.find('code .token').length) {
      Prism.highlightAllUnder($frame[0]);
    }

    highlightCurrentLine();
  };

  var renderCurrentCodeblock = function() {
    var $frame = $('.frame-code-container .frame-code.active');
    if ($frame.length) {
      prepareCodeFrame($frame);
    }
  }

  /*
   * Highlight the active and neighboring lines for the current frame
   * Adjust the offset to make sure that line is veritcally centered
   */

  var highlightCurrentLine = function() {
    var $codeBlock = $activeFrame.find('pre.code-block.line-numbers').first();

    if (!$codeBlock.length) {
      return;
    }

    var scrollToHighlightedLine = function() {
      var codeBlock = $codeBlock[0];
      var highlight = codeBlock.querySelector('.line-highlight');

      if (!highlight) {
        return;
      }

      var lineTop = highlight.offsetTop;
      var lineHeight = highlight.offsetHeight || parseFloat(window.getComputedStyle(codeBlock).lineHeight) || 19;
      var targetScroll = lineTop - (codeBlock.clientHeight / 2) + (lineHeight / 2);

      codeBlock.scrollTop = Math.max(0, targetScroll);
      $container.scrollTop(0);
    };

    // wait for Prism line-highlight layout before scrolling the code block
    requestAnimationFrame(function() {
      requestAnimationFrame(scrollToHighlightedLine);
    });
  }

  var FRAME_FADE_MS = 200;
  var isFrameSwitching = false;

  var bindTransitionEnd = function($node, callback) {
    var done = false;
    var finish = function() {
      if (done) {
        return;
      }
      done = true;
      callback();
    };

    $node.one('transitionend webkitTransitionEnd oTransitionEnd', finish);
    window.setTimeout(finish, FRAME_FADE_MS + 50);
  };

  var showCodeFrame = function($incoming) {
    isFrameSwitching = true;

    $incoming.show().addClass('active is-preparing');
    prepareCodeFrame($incoming);

    $incoming.removeClass('is-preparing').addClass('is-fading-in');
    void $incoming[0].offsetWidth;
    $incoming.addClass('is-fading-in-visible');

    bindTransitionEnd($incoming, function() {
      $incoming.removeClass('is-fading-in is-fading-in-visible');
      isFrameSwitching = false;
    });
  };

  var switchCodeFrame = function($lineItem, id) {
    var $incoming = $('#frame-code-' + id);

    if (!$incoming.length || $lineItem.hasClass('active') || isFrameSwitching) {
      return;
    }

    var $outgoing = $activeFrame;

    $activeLine.removeClass('active');
    $lineItem.addClass('active');
    $activeLine = $lineItem;

    if (!$outgoing.length || $outgoing[0] === $incoming[0]) {
      showCodeFrame($incoming);
      return;
    }

    isFrameSwitching = true;
    $outgoing.addClass('is-fading-out');

    bindTransitionEnd($outgoing, function() {
      $outgoing.removeClass('active is-fading-out').hide();
      showCodeFrame($incoming);
    });
  };

  /*
   * click handler for loading codeblocks
   */

  $frameContainer.on('click', '.frame', function() {

    var $this  = $(this);
    var id     = /frame\-line\-([\d]*)/.exec($this.attr('id'))[1];

    switchCodeFrame($this, id);

  });

  var clipboard = new Clipboard('.clipboard');
  var showTooltip = function(elem, msg) {
    elem.classList.add('tooltipped', 'tooltipped-s');
    elem.setAttribute('aria-label', msg);
  };

  clipboard.on('success', function(e) {
      e.clearSelection();

      showTooltip(e.trigger, 'Copied!');
  });

  clipboard.on('error', function(e) {
      showTooltip(e.trigger, fallbackMessage(e.action));
  });

  var btn = document.querySelector('.clipboard');

  if (btn) {
    btn.addEventListener('mouseleave', function(e) {
      e.currentTarget.classList.remove('tooltipped', 'tooltipped-s');
      e.currentTarget.removeAttribute('aria-label');
    });
  }

  var appendRequestFields = function(form, data, prefix) {
    if (data == null) {
      return;
    }

    if (Object.prototype.toString.call(data) === '[object Array]') {
      for (var i = 0; i < data.length; i++) {
        var indexedName = prefix ? prefix + '[' + i + ']' : String(i);
        appendRequestFieldValue(form, data[i], indexedName);
      }
      return;
    }

    if (typeof data === 'object') {
      for (var key in data) {
        if (!Object.prototype.hasOwnProperty.call(data, key)) {
          continue;
        }
        var fieldName = prefix ? prefix + '[' + key + ']' : key;
        appendRequestFieldValue(form, data[key], fieldName);
      }
    }
  };

  var appendRequestFieldValue = function(form, value, name) {
    if (value !== null && typeof value === 'object') {
      appendRequestFields(form, value, name);
      return;
    }

    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value == null ? '' : String(value);
    form.appendChild(input);
  };

  var buildRequestQuery = function(params, prefix, pairs) {
    pairs = pairs || [];

    if (params == null) {
      return pairs.join('&');
    }

    if (Object.prototype.toString.call(params) === '[object Array]') {
      for (var i = 0; i < params.length; i++) {
        var indexedName = prefix ? prefix + '[' + i + ']' : String(i);
        appendRequestQueryValue(params[i], indexedName, pairs);
      }
      return pairs.join('&');
    }

    if (typeof params === 'object') {
      for (var key in params) {
        if (!Object.prototype.hasOwnProperty.call(params, key)) {
          continue;
        }
        var fieldName = prefix ? prefix + '[' + key + ']' : key;
        appendRequestQueryValue(params[key], fieldName, pairs);
      }
    }

    return pairs.join('&');
  };

  var appendRequestQueryValue = function(value, name, pairs) {
    if (value !== null && typeof value === 'object') {
      buildRequestQuery(value, name, pairs);
      return;
    }

    pairs.push(encodeURIComponent(name) + '=' + encodeURIComponent(value == null ? '' : String(value)));
  };

  var reloadPage = function() {
    var requestNode = document.getElementById('flames-reload-request');
    var request = null;

    if (requestNode) {
      try {
        request = JSON.parse(requestNode.textContent || '{}');
      } catch (error) {
        request = null;
      }
    }

    if (!request) {
      window.location.reload();
      return;
    }

    var method = String(request.method || 'GET').toUpperCase();
    var url = request.url || window.location.pathname;
    var query = buildRequestQuery(request.query);

    if (query) {
      url += (url.indexOf('?') >= 0 ? '&' : '?') + query;
    }

    if (method === 'GET' || method === 'HEAD') {
      window.location.href = url;
      return;
    }

    if (method === 'POST') {
      var form = document.createElement('form');
      form.method = 'POST';
      form.action = url;
      form.style.display = 'none';
      appendRequestFields(form, request.body || {}, '');
      document.body.appendChild(form);
      form.submit();
      return;
    }

    window.location.reload();
  };

  var reloadButton = document.getElementById('reload-button');
  if (reloadButton) {
    reloadButton.addEventListener('click', reloadPage);
  }

  function fallbackMessage(action) {
    var actionMsg = '';
    var actionKey = (action === 'cut' ? 'X' : 'C');

    if (/Mac/i.test(navigator.userAgent)) {
        actionMsg = 'Press ⌘-' + actionKey + ' to ' + action;
    } else {
        actionMsg = 'Press Ctrl-' + actionKey + ' to ' + action;
    }

    return actionMsg;
  }

  function scrollIntoView($node, $parent) {
    var nodeOffset = $node.offset();
    var nodeTop = nodeOffset.top;
    var nodeBottom = nodeTop + nodeOffset.height;
    var parentScrollTop = $parent.scrollTop();
    var parentHeight = $parent.height();

    if (nodeTop < 0) {
      $parent.scrollTop(parentScrollTop + nodeTop);
    } else if (nodeBottom > parentHeight) {
      $parent.scrollTop(parentScrollTop + nodeBottom - parentHeight);
    }
  }

  $(document).on('keydown', function(e) {
    var applicationFrames = $frameContainer.hasClass('frames-container-application'),
        frameClass = applicationFrames ? '.frame.frame-application' : '.frame';

	  if(e.ctrlKey || e.which === 74  || e.which === 75) {
		  // CTRL+Arrow-UP/k and Arrow-Down/j support:
		  // 1) select the next/prev element
		  // 2) make sure the newly selected element is within the view-scope
		  // 3) focus the (right) container, so arrow-up/down (without ctrl) scroll the details
		  if (e.which === 38 /* arrow up */ || e.which === 75 /* k */) {
			  $activeLine.prev(frameClass).click();
			  scrollIntoView($activeLine, $leftPanel);
			  $container.focus();
			  e.preventDefault();
		  } else if (e.which === 40 /* arrow down */ || e.which === 74 /* j */) {
			  $activeLine.next(frameClass).click();
			  scrollIntoView($activeLine, $leftPanel);
			  $container.focus();
			  e.preventDefault();
		  }
	  } else if (e.which == 78 /* n */) {
      if ($appFramesTab.length) {
        setActiveFramesTab($('.frames-tab:not(.frames-tab-active)'));
      }
    }
  });

  // Avoid to quit the page with some protocol (e.g. IntelliJ Platform REST API)
  $ajaxEditors.on('click', function(e){
    e.preventDefault();
    $.get(this.href);
  });

  // Symfony VarDumper: Close the by default expanded objects
  $('.sf-dump-expanded')
    .removeClass('sf-dump-expanded')
    .addClass('sf-dump-compact');
  $('.sf-dump-toggle span').html('&#9654;');

  // Make the given frames-tab active
  function setActiveFramesTab($tab) {
    $tab.addClass('frames-tab-active');

    if ($tab.attr('id') == 'application-frames-tab') {
      $frameContainer.addClass('frames-container-application');
      $allFramesTab.removeClass('frames-tab-active');
    } else {
      $frameContainer.removeClass('frames-container-application');
      $appFramesTab.removeClass('frames-tab-active');
    }
  }

  $('a.frames-tab').on('click', function(e) {
    e.preventDefault();
    setActiveFramesTab($(this));
  });

  // Render late enough for highlightCurrentLine to be ready
  renderCurrentCodeblock();
});
