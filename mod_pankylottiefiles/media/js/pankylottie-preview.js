/**
 * @package     Joomla.Site
 * @subpackage  mod_pankylottiefiles
 *
 * @copyright   (C) 2022 Panayiotis Kiriakopoulos. <https://github.com/pnkr>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Live preview in the module editor. Always autoplays and loops, so the
 * configured trigger is shown as a caption instead of being simulated.
 */

import { setWasmUrl } from './dotlottie-wc.js';

setWasmUrl(new URL('dotlottie-player.wasm', import.meta.url).href);

const preview = document.querySelector('.pankylottie-preview');
const form = preview?.closest('form');

const resolve = (root, url) => {
  const value = (url || '').trim();

  if (value === '' || /^([a-z][a-z0-9+.-]*:)?\/\//i.test(value)) {
    return value;
  }

  return root + value.replace(/^\/+/, '');
};

const readForm = () => {
  const control = preview.dataset.control;
  const data = new FormData(form);
  const get = (name, fallback = '') => data.get(`${control}[${name}]`) ?? fallback;
  const rows = new Map();
  const rowPattern = new RegExp(`^${control.replace(/[[\]]/g, '\\$&')}\\[animations\\]\\[([^\\]]+)\\]\\[(\\w+)\\]$`);

  data.forEach((value, key) => {
    const match = key.match(rowPattern);

    if (match) {
      rows.set(match[1], { ...rows.get(match[1]), [match[2]]: value });
    }
  });

  return {
    options: {
      trigger: get('trigger', 'autoplay'),
      speed: get('speed', '1'),
      mode: get('mode', 'forward'),
      width: get('width', '300px'),
      height: get('height', '300px'),
      ratio: get('aspect_ratio'),
      fit: get('fit', 'contain'),
      background: get('backgroundcolor'),
    },
    animations: [...rows.values()].map((row) => ({
      src: resolve(preview.dataset.root, row.source === 'url' ? row.url : (row.file || '').split('#')[0]),
      start: row.segment_start,
      end: row.segment_end,
      marker: row.marker,
      theme: row.theme,
    })).filter((animation) => animation.src !== ''),
  };
};

const render = ({ options, animations }) => {
  preview.replaceChildren();

  if (!animations.length) {
    const empty = document.createElement('p');
    empty.className = 'pankylottie-preview__empty';
    empty.textContent = preview.dataset.empty;
    preview.append(empty);
    return;
  }

  animations.forEach((animation) => {
    const item = document.createElement('div');
    const player = document.createElement('dotlottie-wc');
    const caption = document.createElement('span');

    item.className = 'pankylottie-preview__item';
    item.style.inlineSize = /^\d+(\.\d+)?(px|%|r?em|vw|vh|ch)$/.test(options.width) ? options.width : '300px';

    if (/^\d+(\.\d+)?\s*([/:]\s*\d+(\.\d+)?)?$/.test(options.ratio.trim())) {
      player.style.aspectRatio = options.ratio.replace(':', '/');
    } else {
      player.style.blockSize = /^\d+(\.\d+)?(px|r?em|vh)$/.test(options.height) ? options.height : '300px';
    }

    player.setAttribute('src', animation.src);
    player.setAttribute('speed', options.speed);
    player.setAttribute('mode', options.mode);
    player.setAttribute('renderconfig', JSON.stringify({ autoResize: true }));
    player.setAttribute('autoplay', '');
    player.setAttribute('loop', '');

    if (/^\d+$/.test(animation.start ?? '') && /^\d+$/.test(animation.end ?? '') && +animation.end > +animation.start) {
      player.setAttribute('segment', JSON.stringify([+animation.start, +animation.end]));
    }

    if (animation.marker) {
      player.setAttribute('marker', animation.marker);
    }

    if (animation.theme) {
      player.setAttribute('themeid', animation.theme);
    }

    if (/^#([0-9a-f]{6}|[0-9a-f]{8})$/i.test(options.background)) {
      player.setAttribute('backgroundcolor', options.background);
    }

    if (options.fit !== 'contain') {
      player.updateComplete?.then(() => player.dotLottie?.addEventListener('load', () => {
        player.dotLottie.setLayout({ fit: options.fit, align: [0.5, 0.5] });
      }));
    }

    caption.className = 'pankylottie-preview__caption';
    caption.textContent = `${options.trigger} · ${options.mode} · ${options.speed}×`;

    item.append(player, caption);
    preview.append(item);
  });
};

if (preview && form) {
  let current = '';

  // Media pickers and subform rows change values without reliable events, so poll cheaply
  const refresh = () => {
    const state = readForm();
    const serialised = JSON.stringify(state);

    if (serialised !== current) {
      current = serialised;
      render(state);
    }
  };

  form.addEventListener('change', refresh);
  window.setInterval(refresh, 800);
  refresh();
}
