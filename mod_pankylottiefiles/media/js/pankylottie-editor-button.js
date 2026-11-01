/**
 * @package     Joomla.Administrator
 * @subpackage  mod_pankylottiefiles
 *
 * @copyright   (C) 2022 Panayiotis Kiriakopoulos. <https://github.com/pnkr>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * "Lottie" editor button: asks for the animation details and inserts a
 * {lottie ...} tag, rendered on the site by the Panky Lottie content plugin.
 */

import { JoomlaEditorButton } from 'editor-api';
import JoomlaDialog from 'joomla.dialog';

const text = (key) => Joomla.Text._(`PLG_EDITORS-XTD_PANKYLOTTIE_${key}`);

// Values live inside double quotes and the tag ends at "}"
const quote = (value) => value.trim().replace(/"/g, '&quot;').replace(/}/g, '');

const field = (form, label, control) => {
  const id = `pankylottie-${control.name}`;
  const wrapper = document.createElement('div');
  const labelElement = document.createElement('label');

  wrapper.className = control.type === 'checkbox' ? 'form-check mb-3' : 'mb-3';
  labelElement.htmlFor = id;
  labelElement.textContent = label;
  labelElement.className = control.type === 'checkbox' ? 'form-check-label' : 'form-label';
  control.id = id;

  if (control.type === 'checkbox') {
    wrapper.append(control, labelElement);
  } else {
    wrapper.append(labelElement, control);
  }

  form.append(wrapper);

  return control;
};

const input = (name, type = 'text', className = 'form-control') => Object.assign(document.createElement('input'), { name, type, className });

const buildForm = () => {
  const form = document.createElement('form');
  form.className = 'p-3';

  const src = field(form, text('SRC'), input('src'));
  src.placeholder = 'images/animation.lottie';
  src.required = true;

  const alt = field(form, text('ALT'), input('alt'));

  const trigger = field(form, text('TRIGGER'), Object.assign(document.createElement('select'), { name: 'trigger', className: 'form-select' }));
  ['autoplay', 'viewport', 'hover', 'click', 'scroll'].forEach((value) => {
    trigger.append(new Option(text(`TRIGGER_${value.toUpperCase()}`), value));
  });

  const loop = field(form, text('LOOP'), input('loop', 'checkbox', 'form-check-input'));
  loop.checked = true;

  return {
    form, src, alt, trigger, loop,
  };
};

JoomlaEditorButton.registerAction('pankylottie-insert', (editor) => {
  const {
    form, src, alt, trigger, loop,
  } = buildForm();
  const dialog = new JoomlaDialog();

  const insert = () => {
    if (!src.reportValidity()) {
      return;
    }

    let tag = `{lottie src="${quote(src.value)}"`;

    if (alt.value.trim() !== '') {
      tag += ` alt="${quote(alt.value)}"`;
    }

    tag += ` trigger="${trigger.value}" loop="${loop.checked ? 1 : 0}"}`;

    editor.replaceSelection(tag);
    dialog.close();
  };

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    insert();
  });

  dialog.popupType = 'inline';
  dialog.textHeader = text('TITLE');
  dialog.popupContent = form;
  dialog.popupButtons = [
    { label: text('INSERT'), data: { buttonOk: '' }, onClick: insert },
    {
      label: Joomla.Text._('JCANCEL', 'Cancel'),
      data: { buttonCancel: '' },
      onClick: () => dialog.close(),
      className: 'button button-secondary btn btn-outline-secondary',
    },
  ];
  dialog.addEventListener('joomla-dialog:close', () => dialog.destroy());
  dialog.show();
  src.focus();
});
