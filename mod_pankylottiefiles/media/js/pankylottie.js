/**
 * @package     Joomla.Site
 * @subpackage  mod_pankylottiefiles
 *
 * @copyright   (C) 2022 Panayiotis Kiriakopoulos. <https://github.com/pnkr>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Lazy-loads the dotLottie player when an animation nears the viewport and wires
 * up its trigger. Players are rendered inside <template> so that none of them
 * initialise before the self-hosted WASM URL is set.
 */

const base = new URL(import.meta.url);
const playerUrl = new URL(`dotlottie-wc.js${base.search}`, base).href;
const wasmUrl = new URL(`dotlottie-player.wasm${base.search}`, base).href;
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const reversed = {
  forward: 'reverse',
  reverse: 'forward',
  bounce: 'reverse-bounce',
  'reverse-bounce': 'bounce',
};

let player;

const loadPlayer = () => {
  player ??= import(playerUrl).then(({ setWasmUrl }) => setWasmUrl(wasmUrl));

  return player;
};

const once = (dotLottie, type) => new Promise((resolve) => {
  const listener = (event) => {
    dotLottie.removeEventListener(type, listener);
    resolve(event);
  };
  dotLottie.addEventListener(type, listener);
});

const whenLoaded = async (element) => {
  await element.updateComplete;

  for (let frame = 0; !element.dotLottie; frame += 1) {
    if (frame > 300) {
      throw new Error('dotLottie player did not initialise');
    }
    // eslint-disable-next-line no-await-in-loop
    await new Promise(requestAnimationFrame);
  }

  const { dotLottie } = element;

  if (!dotLottie.isLoaded) {
    const event = await Promise.race([once(dotLottie, 'load'), once(dotLottie, 'loadError')]);

    if (event?.type === 'loadError') {
      throw event.error ?? new Error(`Could not load ${element.getAttribute('src')}`);
    }
  }

  return dotLottie;
};

const linkToScroll = (root, dotLottie, segment) => {
  const [start, end] = segment ?? [0, Math.max(0, dotLottie.totalFrames - 1)];
  let queued = false;

  const update = () => {
    queued = false;
    const rect = root.getBoundingClientRect();
    const viewport = window.innerHeight;
    const progress = Math.min(1, Math.max(0, (viewport - rect.top) / (viewport + rect.height)));
    dotLottie.setFrame(start + (end - start) * progress);
  };

  const request = () => {
    if (!queued) {
      queued = true;
      requestAnimationFrame(update);
    }
  };

  window.addEventListener('scroll', request, { passive: true });
  window.addEventListener('resize', request, { passive: true });
  update();
};

const setup = async (root) => {
  const config = JSON.parse(root.dataset.pankylottie || '{}');
  const template = root.querySelector('template.pankylottie__template');

  if (!template) {
    return;
  }

  await loadPlayer();

  const element = template.content.querySelector('dotlottie-wc').cloneNode(true);
  template.replaceWith(element);

  const dotLottie = await whenLoaded(element);
  const stage = root.querySelector('.pankylottie__stage');
  const toggle = root.querySelector('.pankylottie__toggle');
  const buttons = [toggle, stage.matches('button') ? stage : null].filter(Boolean);
  const mode = element.getAttribute('mode') || 'forward';
  const loop = element.hasAttribute('loop');

  if (config.fit && config.fit !== 'contain') {
    dotLottie.setLayout({ fit: config.fit, align: [0.5, 0.5] });
  }

  const sync = () => {
    const playing = dotLottie.isPlaying;
    root.classList.toggle('is-paused', !playing);
    buttons.forEach((button) => {
      button.setAttribute('aria-label', playing ? button.dataset.labelPause : button.dataset.labelPlay);
    });
  };

  const play = () => {
    dotLottie.setMode(mode);
    dotLottie.setLoop(loop);
    dotLottie.play();
  };

  const togglePlayback = () => (dotLottie.isPlaying ? dotLottie.pause() : play());

  ['play', 'pause', 'stop', 'complete'].forEach((type) => dotLottie.addEventListener(type, sync));
  buttons.forEach((button) => button.addEventListener('click', togglePlayback));

  if (toggle) {
    toggle.hidden = false;
  }

  root.classList.add('is-ready');
  sync();

  // Visitors who asked for less motion get the first frame; the controls still work
  if (config.reducedMotion && prefersReducedMotion.matches) {
    return;
  }

  switch (config.trigger) {
    case 'viewport': {
      const observer = new IntersectionObserver(([entry]) => {
        if (entry.isIntersecting) {
          observer.disconnect();
          play();
        }
      }, { threshold: 0.5 });
      observer.observe(root);
      break;
    }

    case 'hover': {
      const leave = () => {
        if (config.hoverOut === 'reverse') {
          dotLottie.setMode(reversed[mode]);
          dotLottie.setLoop(false);
          dotLottie.play();
        } else if (config.hoverOut === 'pause') {
          dotLottie.pause();
        } else {
          dotLottie.stop();
        }
      };
      root.addEventListener('pointerenter', play);
      root.addEventListener('pointerleave', leave);
      root.addEventListener('focusin', play);
      root.addEventListener('focusout', leave);
      break;
    }

    case 'scroll':
      linkToScroll(root, dotLottie, config.segment);
      break;

    case 'click':
      break;

    default:
      play();
  }
};

const init = () => {
  const roots = [...document.querySelectorAll('.pankylottie[data-pankylottie]:not(.is-initialised)')];

  const start = (root) => {
    root.classList.add('is-initialised');
    setup(root).catch((error) => {
      root.classList.add('is-failed');
      // eslint-disable-next-line no-console
      console.error('[Panky Lottie]', error);
    });
  };

  if (!('IntersectionObserver' in window)) {
    roots.forEach(start);
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.filter((entry) => entry.isIntersecting).forEach(({ target }) => {
      observer.unobserve(target);
      start(target);
    });
  }, { rootMargin: '200px' });

  roots.forEach((root) => observer.observe(root));
};

init();
document.addEventListener('joomla:updated', init);
