// Единый линейный набор иконок, viewBox 32x32. Стиль (stroke) задаётся классом .ico
export const ICONS = {
  // --- интерфейс ---
  phone: '<path d="M9 5h4l2 6-3 2a15 15 0 0 0 7 7l2-3 6 2v4a3 3 0 0 1-3 3A20 20 0 0 1 6 8a3 3 0 0 1 3-3z"/>',
  telegram: '<path d="M28 4 3 14l8 3 3 9 4-6 7 5z"/><path d="m11 17 17-13"/>',
  max: '<path d="M6 9a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3H14l-6 5v-5a3 3 0 0 1-2-3z"/><path d="M11 13.5h.01M16 13.5h.01M21 13.5h.01" stroke-width="2.6"/>',
  check: '<path d="m6 17 7 7L26 9"/>',
  shield: '<path d="M16 3 27 7v8c0 7-5 11-11 14C10 26 5 22 5 15V7z"/>',
  'shield-check': '<path d="M16 3 27 7v8c0 7-5 11-11 14C10 26 5 22 5 15V7z"/><path d="m11 16 4 4 7-8"/>',
  clock: '<circle cx="16" cy="16" r="12"/><path d="M16 9v7l5 3"/>',
  leaf: '<path d="M6 26C6 12 14 6 27 5c0 13-6 21-19 21z"/><path d="M6 26 18 14"/>',
  star: '<path d="m16 3 3.9 8.2 8.9 1.2-6.5 6.2 1.7 8.9L16 23l-8 4.5 1.7-8.9-6.5-6.2 8.9-1.2z"/>',
  pin: '<path d="M16 29s9-8 9-15a9 9 0 0 0-18 0c0 7 9 15 9 15z"/><circle cx="16" cy="14" r="3"/>',
  mail: '<rect x="3" y="7" width="26" height="18" rx="3"/><path d="m4 9 12 9 12-9"/>',
  menu: '<path d="M5 9h22M5 16h22M5 23h22"/>',
  close: '<path d="M7 7l18 18M25 7 7 25"/>',
  play: '<path d="M10 6v20l17-10z"/>',
  arrow: '<path d="M5 16h21M19 9l7 7-7 7"/>',
  doc: '<path d="M8 3h11l6 6v20H8zM19 3v6h6M12 15h9M12 20h9M12 25h6"/>',
  badge: '<circle cx="16" cy="13" r="8"/><path d="m11.5 20-2 9 6.5-3.5 6.5 3.5-2-9M12.5 13l2.5 2.5 4.5-5"/>',
  spray: '<path d="M8 14h10v14H8zM10 14v-4h6v4M16 10h7l3-3M27 12l3 1M26 16l3 2"/>',
  drop: '<path d="M16 4c5 7 9 11 9 16a9 9 0 0 1-18 0c0-5 4-9 9-16z"/>',
  calc: '<rect x="6" y="3" width="20" height="26" rx="3"/><path d="M10 8h12v5H10zM11 18h.01M16 18h.01M21 18h.01M11 23h.01M16 23h.01M21 23h.01" stroke-width="2.4"/>',
  ruble: '<path d="M11 27V5h7a6 6 0 0 1 0 12h-7M8 21h11"/>',
  search: '<circle cx="14" cy="14" r="8"/><path d="m20 20 8 8"/>',
  truck: '<path d="M3 21V9h15v12M18 13h6l5 5v3h-4M3 21h3M12 21h8"/><circle cx="9" cy="23" r="2.5"/><circle cx="23" cy="23" r="2.5"/>',
  clipboard: '<rect x="6" y="6" width="20" height="23" rx="3"/><path d="M12 6V4h8v2M11 16l3 3 6-6M11 24h10"/>',
  refresh: '<path d="M26 12a10 10 0 0 0-18-3M6 4v6h6M6 20a10 10 0 0 0 18 3M26 28v-6h-6"/>',
  chat: '<path d="M6 9a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3H14l-6 5v-5a3 3 0 0 1-2-3z"/>',
  // --- вредители ---
  tarakan: '<ellipse cx="16" cy="19" rx="5.5" ry="8.5"/><circle cx="16" cy="9" r="2.6"/><path d="M15 7C13 4 10 3 7 4M17 7c2-3 5-4 8-3M11 15 5 12M10.5 19H4M11 23l-6 4M21 15l6-3M21.5 19H28M21 23l6 4M16 12v13"/>',
  klop: '<ellipse cx="16" cy="18" rx="7.5" ry="8.5"/><circle cx="16" cy="8" r="2.4"/><path d="M15 6 12 3M17 6l3-3M8.6 15 4 12M8.5 19H3M9.5 23 5 27M23.4 15 28 12M23.5 19H29M22.5 23l4.5 4M10 18c4 2 8 2 12 0"/>',
  blokha: '<path d="M8 19c0-6 4-10 10-10 4 0 6 3 6 7 0 5-3 8-8 8-5 0-8-2-8-5z"/><path d="M8 19 5 17 3 13M13 24l-2 5M17 24v5M21 23l4 0 3 6M12 13l-2-3"/><circle cx="12.5" cy="15" r=".8"/>',
  muravei: '<circle cx="16" cy="7" r="2.6"/><circle cx="16" cy="15" r="2.4"/><ellipse cx="16" cy="24" rx="4" ry="5"/><path d="M14.5 5 11 2M17.5 5 21 2M14 14 8 11M14 16l-7 3M14 18l-5 7M18 14l6-3M18 16l7 3M18 18l5 7"/>',
  mokritsa: '<path d="M4 21c0-7 5-13 12-13s12 6 12 13z"/><path d="M10 10v11M16 8v13M22 10v11M4 21h24M7 21l-2 4M11 21l-1 5M16 21v5M21 21l1 5M25 21l2 4"/>',
  muha: '<ellipse cx="16" cy="19" rx="3.6" ry="7"/><circle cx="16" cy="9.5" r="3"/><path d="M13 16C6 14 3 8 7 6c4-1 7 4 7 9M19 16c7-2 10-8 6-10-4-1-7 4-7 9M13 21l-4 4M19 21l4 4M14 24l-2 5M18 24l2 5"/>',
  osa: '<circle cx="16" cy="7" r="2.5"/><circle cx="16" cy="13" r="3"/><ellipse cx="16" cy="23" rx="4" ry="6"/><path d="M12.3 21h7.4M12 25h8M18 11c4-4 9-5 9-1 0 3-5 5-8 5M14 11C10 7 5 6 5 10c0 3 5 5 8 5M16 29v3"/>',
  komar: '<circle cx="16" cy="6" r="2"/><path d="M16 8v14M16 4V1M16 12c6-6 11-6 12-3-1 3-7 4-12 4M16 12C10 6 5 6 4 9c1 3 7 4 12 4M16 16l-8 8-2 5M16 16l8 8 2 5M16 18l-2 10M16 18l2 10"/>',
  klesh: '<ellipse cx="16" cy="19" rx="7" ry="7.5"/><circle cx="16" cy="9" r="2.2"/><path d="M9.5 15 4 11M9 18.5 3 18M9.5 22 4 26M12 25.5 9 30M22.5 15 28 11M23 18.5 29 18M22.5 22 28 26M20 25.5 23 30M13 15c2 1 4 1 6 0"/>',
  tlya: '<path d="M16 11c-5 0-8 4-8 8.5S11 27 16 27s8-3 8-7.5-3-8.5-8-8.5z"/><circle cx="16" cy="8.5" r="2.4"/><path d="M14.5 6.5 11 3M17.5 6.5 21 3M9 20l-5 2M9.5 24 6 28M23 20l5 2M22.5 24l3.5 4M13 26l-1 4M19 26l1 4"/>',
  krysa: '<ellipse cx="14" cy="19" rx="9" ry="6"/><path d="M22 15l7 3-7 3M5 20C1 20 1 13 4 10M11 25v3M18 25v3"/><circle cx="21" cy="14" r="2.6"/><circle cx="24" cy="18" r=".8"/>',
  mysh: '<ellipse cx="14" cy="20" rx="7.5" ry="5.5"/><path d="M20.5 17l6 3-6 3M6.5 21C2 21 2 15 5 11c2-3 5-2 4 1M10 25v3M17 25v3"/><circle cx="18" cy="13.5" r="3.6"/><circle cx="24" cy="20" r=".8"/>',
  plesen: '<circle cx="12" cy="19" r="6"/><circle cx="22" cy="14" r="4.5"/><circle cx="21" cy="24" r="3.5"/><path d="M12 13V9M7.5 15 5 12.5M6 20H3M24 8.5 26 6M27 14h3M17 8 16 5"/>',
  virus: '<circle cx="16" cy="16" r="6"/><path d="M16 3v5M16 24v5M3 16h5M24 16h5M7 7l3.5 3.5M21.5 21.5 25 25M25 7l-3.5 3.5M10.5 21.5 7 25"/><circle cx="16" cy="3" r="1"/><circle cx="16" cy="29" r="1"/><circle cx="3" cy="16" r="1"/><circle cx="29" cy="16" r="1"/>',
  fungus: '<path d="M6 26C6 12 14 6 27 5c0 13-6 21-19 21z"/><circle cx="17" cy="14" r="1.6"/><circle cx="13" cy="19" r="1.3"/><circle cx="20" cy="19" r="1.2"/><path d="M6 26 12 20"/>',
  // --- сегменты клиентов ---
  home: '<path d="M4 15 16 5l12 10M7 13v14h18V13M13 27v-8h6v8"/>',
  sprout: '<path d="M16 28V16M16 16C16 10 12 7 6 7c0 6 4 9 10 9M16 19c0-5 4-8 10-8 0 6-4 9-10 9"/>',
  building: '<path d="M7 28V5h14v23M21 12h5v16M4 28h24M11 10h2M15 10h2M11 15h2M15 15h2M11 20h2M15 20h2"/>',
  utensils: '<path d="M9 4v7M13 4v7M9 11a2 2 0 0 0 2 2h0a2 2 0 0 0 2-2M11 13v15M22 28V4c-3 2-5 6-5 11h5"/>',
  warehouse: '<path d="M3 13 16 5l13 8v15H3zM9 28V18h14v10M9 23h14"/>',
  buildings: '<path d="M4 28V11l7-4 7 4v17M18 28V15h10v13M8 15h2M8 20h2M12 15h2M12 20h2M21 19h4M21 23h4M2 28h28"/>',
  cross: '<rect x="5" y="5" width="22" height="22" rx="5"/><path d="M16 10v12M10 16h12"/>'
};

export function sprite() {
  const symbols = Object.entries(ICONS)
    .map(([id, d]) => `<symbol id="i-${id}" viewBox="0 0 32 32">${d}</symbol>`)
    .join('');
  return `<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">${symbols}</svg>`;
}

export const icon = (id, cls = '') =>
  `<svg class="ico${cls ? ' ' + cls : ''}" aria-hidden="true" focusable="false"><use href="#i-${id}"/></svg>`;
