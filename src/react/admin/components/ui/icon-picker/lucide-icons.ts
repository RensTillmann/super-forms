/**
 * Lucide React icons - Direct Dynamic Import Approach
 * Loads icons on-demand without bundling the entire icon library
 * https://lucide.dev/icons/
 */
import type { LucideIcon } from 'lucide-react';

export interface LucideIconInfo {
  name: string;
  tags: string[];
}

/**
 * Convert kebab-case to PascalCase for icon imports
 * e.g., "arrow-right" -> "ArrowRight"
 */
function toPascalCase(str: string): string {
  return str
    .split('-')
    .map(part => part.charAt(0).toUpperCase() + part.slice(1))
    .join('');
}

/**
 * Dynamic icon loader - loads icon component on demand
 * Uses direct import() to leverage Vite's code-splitting
 */
export async function loadLucideIcon(name: string): Promise<LucideIcon | null> {
  try {
    const pascalName = toPascalCase(name);
    // Dynamic import from lucide-react's individual icon exports
    const module = await import(`lucide-react/dist/esm/icons/${name}.js`);
    return module.default || module[pascalName] || null;
  } catch {
    return null;
  }
}

/**
 * Curated list of commonly used icons with search tags
 * This provides better search UX than showing all 1800+ icons
 */
export const lucideIcons: LucideIconInfo[] = [
  // Actions
  { name: 'check', tags: ['done', 'complete', 'success', 'yes'] },
  { name: 'check-circle', tags: ['done', 'complete', 'success', 'verified'] },
  { name: 'check-square', tags: ['done', 'complete', 'checkbox'] },
  { name: 'x', tags: ['close', 'cancel', 'delete', 'remove'] },
  { name: 'x-circle', tags: ['close', 'cancel', 'error'] },
  { name: 'plus', tags: ['add', 'new', 'create'] },
  { name: 'plus-circle', tags: ['add', 'new', 'create'] },
  { name: 'minus', tags: ['subtract', 'remove', 'decrease'] },
  { name: 'minus-circle', tags: ['subtract', 'remove'] },
  { name: 'pencil', tags: ['edit', 'write', 'modify'] },
  { name: 'pencil-line', tags: ['edit', 'write', 'modify'] },
  { name: 'trash', tags: ['delete', 'remove', 'bin'] },
  { name: 'trash-2', tags: ['delete', 'remove', 'bin'] },
  { name: 'save', tags: ['disk', 'store', 'floppy'] },
  { name: 'download', tags: ['save', 'export', 'arrow'] },
  { name: 'upload', tags: ['import', 'arrow'] },
  { name: 'copy', tags: ['duplicate', 'clone'] },
  { name: 'clipboard', tags: ['paste', 'copy'] },
  { name: 'clipboard-check', tags: ['paste', 'done'] },
  { name: 'clipboard-list', tags: ['paste', 'todo'] },
  { name: 'refresh-cw', tags: ['reload', 'sync', 'update'] },
  { name: 'rotate-cw', tags: ['redo', 'clockwise'] },
  { name: 'rotate-ccw', tags: ['undo', 'counterclockwise'] },
  { name: 'undo', tags: ['back', 'revert'] },
  { name: 'redo', tags: ['forward', 'redo'] },

  // Arrows
  { name: 'arrow-up', tags: ['direction', 'up'] },
  { name: 'arrow-down', tags: ['direction', 'down'] },
  { name: 'arrow-left', tags: ['direction', 'left', 'back'] },
  { name: 'arrow-right', tags: ['direction', 'right', 'forward'] },
  { name: 'arrow-up-right', tags: ['direction', 'diagonal'] },
  { name: 'arrow-up-left', tags: ['direction', 'diagonal'] },
  { name: 'arrow-down-right', tags: ['direction', 'diagonal'] },
  { name: 'arrow-down-left', tags: ['direction', 'diagonal'] },
  { name: 'chevron-up', tags: ['expand', 'collapse'] },
  { name: 'chevron-down', tags: ['expand', 'collapse', 'dropdown'] },
  { name: 'chevron-left', tags: ['back', 'previous'] },
  { name: 'chevron-right', tags: ['forward', 'next'] },
  { name: 'chevrons-up', tags: ['double', 'fast'] },
  { name: 'chevrons-down', tags: ['double', 'fast'] },
  { name: 'chevrons-left', tags: ['double', 'fast'] },
  { name: 'chevrons-right', tags: ['double', 'fast'] },
  { name: 'move', tags: ['drag', 'reorder'] },
  { name: 'move-horizontal', tags: ['drag', 'resize'] },
  { name: 'move-vertical', tags: ['drag', 'resize'] },
  { name: 'external-link', tags: ['open', 'new window'] },

  // Communication
  { name: 'mail', tags: ['email', 'envelope', 'message'] },
  { name: 'mail-open', tags: ['email', 'read'] },
  { name: 'inbox', tags: ['email', 'messages'] },
  { name: 'send', tags: ['email', 'submit', 'arrow'] },
  { name: 'message-circle', tags: ['chat', 'comment', 'bubble'] },
  { name: 'message-square', tags: ['chat', 'comment', 'bubble'] },
  { name: 'messages-square', tags: ['chat', 'conversation'] },
  { name: 'phone', tags: ['call', 'contact', 'telephone'] },
  { name: 'phone-call', tags: ['ringing', 'incoming'] },
  { name: 'phone-incoming', tags: ['call', 'receive'] },
  { name: 'phone-outgoing', tags: ['call', 'dial'] },
  { name: 'phone-missed', tags: ['call', 'missed'] },
  { name: 'phone-off', tags: ['call', 'decline'] },
  { name: 'video', tags: ['camera', 'record', 'film'] },
  { name: 'video-off', tags: ['camera', 'disabled'] },
  { name: 'at-sign', tags: ['email', 'mention'] },
  { name: 'hash', tags: ['hashtag', 'number', 'tag'] },

  // Media
  { name: 'image', tags: ['photo', 'picture', 'gallery'] },
  { name: 'images', tags: ['photos', 'gallery', 'album'] },
  { name: 'camera', tags: ['photo', 'picture', 'snapshot'] },
  { name: 'film', tags: ['video', 'movie', 'cinema'] },
  { name: 'music', tags: ['audio', 'song', 'sound'] },
  { name: 'mic', tags: ['microphone', 'audio', 'record'] },
  { name: 'mic-off', tags: ['microphone', 'mute'] },
  { name: 'volume', tags: ['sound', 'audio', 'speaker'] },
  { name: 'volume-1', tags: ['sound', 'audio', 'low'] },
  { name: 'volume-2', tags: ['sound', 'audio', 'high'] },
  { name: 'volume-x', tags: ['mute', 'silent'] },
  { name: 'play', tags: ['start', 'video', 'audio'] },
  { name: 'pause', tags: ['stop', 'video', 'audio'] },
  { name: 'play-circle', tags: ['start', 'video'] },
  { name: 'pause-circle', tags: ['stop', 'video'] },
  { name: 'circle-stop', tags: ['end', 'video'] },
  { name: 'skip-back', tags: ['previous', 'rewind'] },
  { name: 'skip-forward', tags: ['next', 'fast forward'] },
  { name: 'rewind', tags: ['back', 'previous'] },
  { name: 'fast-forward', tags: ['next', 'skip'] },

  // Files & Documents
  { name: 'file', tags: ['document', 'page'] },
  { name: 'file-text', tags: ['document', 'page', 'text'] },
  { name: 'file-plus', tags: ['document', 'new', 'add'] },
  { name: 'file-minus', tags: ['document', 'remove'] },
  { name: 'file-check', tags: ['document', 'done'] },
  { name: 'file-x', tags: ['document', 'delete'] },
  { name: 'file-edit', tags: ['document', 'modify'] },
  { name: 'file-search', tags: ['document', 'find'] },
  { name: 'files', tags: ['documents', 'multiple'] },
  { name: 'folder', tags: ['directory', 'archive'] },
  { name: 'folder-open', tags: ['directory', 'browse'] },
  { name: 'folder-plus', tags: ['directory', 'new'] },
  { name: 'folder-minus', tags: ['directory', 'remove'] },
  { name: 'archive', tags: ['zip', 'compress', 'box'] },
  { name: 'paperclip', tags: ['attachment', 'clip'] },

  // Navigation & Layout
  { name: 'home', tags: ['house', 'main', 'dashboard'] },
  { name: 'menu', tags: ['hamburger', 'navigation', 'bars'] },
  { name: 'ellipsis', tags: ['more', 'dots', 'options', 'horizontal'] },
  { name: 'ellipsis-vertical', tags: ['more', 'dots', 'options', 'vertical'] },
  { name: 'grid-3x3', tags: ['layout', 'tiles', 'gallery'] },
  { name: 'list', tags: ['layout', 'rows', 'items'] },
  { name: 'layout-dashboard', tags: ['template', 'design'] },
  { name: 'layout-grid', tags: ['template', 'design'] },
  { name: 'layout-list', tags: ['template', 'design'] },
  { name: 'columns-3', tags: ['layout', 'split'] },
  { name: 'rows-3', tags: ['layout', 'horizontal'] },
  { name: 'sidebar', tags: ['layout', 'panel'] },
  { name: 'panel-left', tags: ['layout', 'sidebar'] },
  { name: 'panel-right', tags: ['layout', 'sidebar'] },
  { name: 'maximize', tags: ['fullscreen', 'expand'] },
  { name: 'maximize-2', tags: ['fullscreen', 'expand'] },
  { name: 'minimize', tags: ['shrink', 'collapse'] },
  { name: 'minimize-2', tags: ['shrink', 'collapse'] },

  // User & Account
  { name: 'user', tags: ['person', 'account', 'profile'] },
  { name: 'user-plus', tags: ['add', 'new', 'register'] },
  { name: 'user-minus', tags: ['remove', 'delete'] },
  { name: 'user-check', tags: ['verified', 'approved'] },
  { name: 'user-x', tags: ['blocked', 'banned'] },
  { name: 'users', tags: ['people', 'team', 'group'] },
  { name: 'circle-user', tags: ['avatar', 'profile'] },
  { name: 'contact', tags: ['person', 'profile'] },
  { name: 'log-in', tags: ['signin', 'enter', 'login'] },
  { name: 'log-out', tags: ['signout', 'exit', 'logout'] },

  // Security
  { name: 'lock', tags: ['secure', 'private', 'password'] },
  { name: 'lock-open', tags: ['unlock', 'open', 'public'] },
  { name: 'key', tags: ['password', 'access', 'security'] },
  { name: 'shield', tags: ['security', 'protect', 'safe'] },
  { name: 'shield-check', tags: ['security', 'verified', 'safe'] },
  { name: 'shield-alert', tags: ['security', 'warning'] },
  { name: 'shield-off', tags: ['security', 'disabled'] },
  { name: 'eye', tags: ['view', 'visible', 'show'] },
  { name: 'eye-off', tags: ['hide', 'invisible', 'hidden'] },

  // Status & Alerts
  { name: 'circle-alert', tags: ['warning', 'error', 'attention'] },
  { name: 'triangle-alert', tags: ['warning', 'caution', 'danger'] },
  { name: 'octagon-alert', tags: ['stop', 'error', 'critical'] },
  { name: 'info', tags: ['information', 'help', 'about'] },
  { name: 'circle-help', tags: ['question', 'support', 'faq'] },
  { name: 'bell', tags: ['notification', 'alert', 'alarm'] },
  { name: 'bell-off', tags: ['notification', 'mute', 'silent'] },
  { name: 'bell-ring', tags: ['notification', 'alert', 'ringing'] },
  { name: 'badge', tags: ['label', 'tag', 'status'] },
  { name: 'badge-check', tags: ['verified', 'approved'] },

  // Time & Calendar
  { name: 'clock', tags: ['time', 'hour', 'watch'] },
  { name: 'timer', tags: ['countdown', 'stopwatch'] },
  { name: 'calendar', tags: ['date', 'schedule', 'event'] },
  { name: 'calendar-days', tags: ['date', 'schedule'] },
  { name: 'calendar-check', tags: ['date', 'confirmed'] },
  { name: 'calendar-plus', tags: ['date', 'new event'] },
  { name: 'calendar-minus', tags: ['date', 'remove'] },
  { name: 'calendar-x', tags: ['date', 'cancel'] },
  { name: 'history', tags: ['time', 'past', 'log'] },

  // Settings & Tools
  { name: 'settings', tags: ['gear', 'cog', 'preferences'] },
  { name: 'settings-2', tags: ['gear', 'cog', 'preferences'] },
  { name: 'sliders-horizontal', tags: ['controls', 'adjust', 'settings'] },
  { name: 'sliders-vertical', tags: ['controls', 'adjust'] },
  { name: 'wrench', tags: ['tool', 'repair', 'fix'] },
  { name: 'hammer', tags: ['tool', 'build', 'construct'] },
  { name: 'filter', tags: ['sort', 'funnel', 'refine'] },
  { name: 'search', tags: ['find', 'magnify', 'look'] },
  { name: 'zoom-in', tags: ['magnify', 'enlarge'] },
  { name: 'zoom-out', tags: ['shrink', 'reduce'] },

  // Data & Charts
  { name: 'bar-chart', tags: ['graph', 'analytics', 'statistics'] },
  { name: 'bar-chart-2', tags: ['graph', 'analytics'] },
  { name: 'bar-chart-3', tags: ['graph', 'analytics'] },
  { name: 'chart-line', tags: ['graph', 'trend', 'analytics'] },
  { name: 'chart-pie', tags: ['graph', 'analytics', 'donut'] },
  { name: 'activity', tags: ['pulse', 'heartbeat', 'analytics'] },
  { name: 'trending-up', tags: ['growth', 'increase', 'profit'] },
  { name: 'trending-down', tags: ['decline', 'decrease', 'loss'] },
  { name: 'percent', tags: ['discount', 'rate', 'percentage'] },
  { name: 'database', tags: ['storage', 'data', 'server'] },
  { name: 'table', tags: ['data', 'spreadsheet', 'grid'] },
  { name: 'table-2', tags: ['data', 'spreadsheet'] },

  // Shopping & Commerce
  { name: 'shopping-cart', tags: ['buy', 'ecommerce', 'basket'] },
  { name: 'shopping-bag', tags: ['buy', 'ecommerce', 'store'] },
  { name: 'package', tags: ['box', 'shipping', 'delivery'] },
  { name: 'gift', tags: ['present', 'reward', 'bonus'] },
  { name: 'credit-card', tags: ['payment', 'buy', 'money'] },
  { name: 'wallet', tags: ['money', 'payment', 'finance'] },
  { name: 'dollar-sign', tags: ['money', 'currency', 'price'] },
  { name: 'euro', tags: ['money', 'currency', 'price'] },
  { name: 'receipt', tags: ['invoice', 'bill', 'transaction'] },
  { name: 'tag', tags: ['label', 'price', 'category'] },
  { name: 'tags', tags: ['labels', 'categories'] },

  // Location & Maps
  { name: 'map', tags: ['location', 'geography', 'navigation'] },
  { name: 'map-pin', tags: ['location', 'marker', 'place'] },
  { name: 'navigation', tags: ['compass', 'direction', 'location'] },
  { name: 'navigation-2', tags: ['compass', 'direction'] },
  { name: 'compass', tags: ['direction', 'navigation'] },
  { name: 'globe', tags: ['world', 'earth', 'international'] },
  { name: 'globe-2', tags: ['world', 'earth'] },
  { name: 'locate', tags: ['gps', 'position', 'find'] },
  { name: 'locate-fixed', tags: ['gps', 'position'] },

  // Weather
  { name: 'sun', tags: ['weather', 'light', 'day', 'bright'] },
  { name: 'moon', tags: ['weather', 'night', 'dark'] },
  { name: 'cloud', tags: ['weather', 'sky', 'storage'] },
  { name: 'cloud-rain', tags: ['weather', 'rainy'] },
  { name: 'cloud-snow', tags: ['weather', 'snowy', 'winter'] },
  { name: 'cloud-lightning', tags: ['weather', 'storm', 'thunder'] },
  { name: 'cloud-off', tags: ['offline', 'disconnected'] },
  { name: 'thermometer', tags: ['temperature', 'weather', 'heat'] },
  { name: 'droplet', tags: ['water', 'liquid', 'rain'] },
  { name: 'wind', tags: ['weather', 'air', 'breeze'] },

  // Social & Sharing
  { name: 'share', tags: ['social', 'send', 'forward'] },
  { name: 'share-2', tags: ['social', 'network', 'connect'] },
  { name: 'link', tags: ['url', 'chain', 'connect'] },
  { name: 'link-2', tags: ['url', 'chain'] },
  { name: 'unlink', tags: ['disconnect', 'broken'] },
  { name: 'heart', tags: ['like', 'love', 'favorite'] },
  { name: 'thumbs-up', tags: ['like', 'approve', 'good'] },
  { name: 'thumbs-down', tags: ['dislike', 'reject', 'bad'] },
  { name: 'star', tags: ['favorite', 'rating', 'bookmark'] },
  { name: 'bookmark', tags: ['save', 'favorite', 'flag'] },
  { name: 'flag', tags: ['report', 'mark', 'country'] },
  { name: 'award', tags: ['achievement', 'trophy', 'prize'] },
  { name: 'trophy', tags: ['achievement', 'winner', 'award'] },

  // Devices
  { name: 'smartphone', tags: ['mobile', 'phone', 'device'] },
  { name: 'tablet', tags: ['ipad', 'device', 'screen'] },
  { name: 'laptop', tags: ['computer', 'device', 'notebook'] },
  { name: 'monitor', tags: ['screen', 'display', 'desktop'] },
  { name: 'tv', tags: ['television', 'screen', 'display'] },
  { name: 'printer', tags: ['print', 'document', 'output'] },
  { name: 'keyboard', tags: ['type', 'input', 'keys'] },
  { name: 'mouse', tags: ['cursor', 'click', 'input'] },
  { name: 'headphones', tags: ['audio', 'music', 'listen'] },
  { name: 'speaker', tags: ['audio', 'sound', 'volume'] },
  { name: 'cpu', tags: ['processor', 'chip', 'hardware'] },
  { name: 'hard-drive', tags: ['storage', 'disk', 'ssd'] },
  { name: 'usb', tags: ['port', 'connect', 'drive'] },
  { name: 'wifi', tags: ['wireless', 'internet', 'signal'] },
  { name: 'wifi-off', tags: ['offline', 'disconnected'] },
  { name: 'bluetooth', tags: ['wireless', 'connect'] },
  { name: 'battery', tags: ['power', 'charge', 'energy'] },
  { name: 'battery-charging', tags: ['power', 'charge'] },
  { name: 'battery-full', tags: ['power', 'charged'] },
  { name: 'battery-low', tags: ['power', 'empty'] },
  { name: 'plug', tags: ['power', 'electric', 'connect'] },
  { name: 'power', tags: ['on', 'off', 'switch'] },

  // Development & Code
  { name: 'code', tags: ['programming', 'development', 'html'] },
  { name: 'code-2', tags: ['programming', 'brackets'] },
  { name: 'terminal', tags: ['command', 'console', 'cli'] },
  { name: 'bug', tags: ['error', 'debug', 'issue'] },
  { name: 'git-branch', tags: ['version', 'code', 'merge'] },
  { name: 'git-commit', tags: ['version', 'code', 'save'] },
  { name: 'git-merge', tags: ['version', 'code', 'combine'] },
  { name: 'git-pull-request', tags: ['version', 'code', 'pr'] },
  { name: 'github', tags: ['code', 'repository', 'git'] },
  { name: 'gitlab', tags: ['code', 'repository', 'git'] },
  { name: 'box', tags: ['package', 'container', 'module'] },
  { name: 'layers', tags: ['stack', 'design', 'z-index'] },
  { name: 'component', tags: ['module', 'block', 'element'] },

  // Text & Typography
  { name: 'type', tags: ['text', 'font', 'typography'] },
  { name: 'bold', tags: ['text', 'font', 'strong'] },
  { name: 'italic', tags: ['text', 'font', 'emphasis'] },
  { name: 'underline', tags: ['text', 'font', 'decoration'] },
  { name: 'strikethrough', tags: ['text', 'font', 'delete'] },
  { name: 'align-left', tags: ['text', 'paragraph'] },
  { name: 'align-center', tags: ['text', 'paragraph'] },
  { name: 'align-right', tags: ['text', 'paragraph'] },
  { name: 'align-justify', tags: ['text', 'paragraph'] },
  { name: 'list-ordered', tags: ['text', 'numbers', 'ol'] },
  { name: 'list', tags: ['text', 'bullets', 'ul'] },
  { name: 'indent-increase', tags: ['text', 'paragraph', 'tab'] },
  { name: 'indent-decrease', tags: ['text', 'paragraph', 'tab'] },
  { name: 'quote', tags: ['text', 'blockquote', 'citation'] },
  { name: 'heading', tags: ['text', 'title', 'h1'] },
  { name: 'heading-1', tags: ['text', 'title', 'h1'] },
  { name: 'heading-2', tags: ['text', 'title', 'h2'] },
  { name: 'heading-3', tags: ['text', 'title', 'h3'] },

  // Shapes
  { name: 'circle', tags: ['shape', 'round', 'dot'] },
  { name: 'square', tags: ['shape', 'box', 'rectangle'] },
  { name: 'triangle', tags: ['shape', 'polygon'] },
  { name: 'pentagon', tags: ['shape', 'polygon'] },
  { name: 'hexagon', tags: ['shape', 'polygon'] },
  { name: 'octagon', tags: ['shape', 'polygon', 'stop'] },
  { name: 'diamond', tags: ['shape', 'rhombus'] },

  // Form Elements
  { name: 'text-cursor-input', tags: ['field', 'text', 'input'] },
  { name: 'text-cursor', tags: ['input', 'type', 'caret'] },
  { name: 'toggle-left', tags: ['switch', 'off', 'disable'] },
  { name: 'toggle-right', tags: ['switch', 'on', 'enable'] },
  { name: 'circle-dot', tags: ['radio', 'option', 'select', 'choice'] },
  { name: 'square-check', tags: ['checkbox', 'done', 'select'] },

  // Misc
  { name: 'sparkles', tags: ['magic', 'new', 'ai', 'special'] },
  { name: 'wand', tags: ['magic', 'wizard', 'auto'] },
  { name: 'wand-sparkles', tags: ['magic', 'wizard', 'auto'] },
  { name: 'zap', tags: ['lightning', 'fast', 'power', 'flash'] },
  { name: 'zap-off', tags: ['lightning', 'disabled'] },
  { name: 'flame', tags: ['fire', 'hot', 'trending'] },
  { name: 'rocket', tags: ['launch', 'fast', 'startup'] },
  { name: 'anchor', tags: ['link', 'marine', 'stable'] },
  { name: 'target', tags: ['goal', 'aim', 'focus'] },
  { name: 'crosshair', tags: ['target', 'aim', 'focus'] },
  { name: 'infinity', tags: ['forever', 'loop', 'unlimited'] },
  { name: 'loader', tags: ['loading', 'spinner', 'wait'] },
  { name: 'loader-2', tags: ['loading', 'spinner', 'wait'] },
  { name: 'refresh-ccw', tags: ['reload', 'sync', 'update'] },
  { name: 'palette', tags: ['color', 'design', 'theme'] },
  { name: 'paintbrush', tags: ['color', 'design', 'draw'] },
  { name: 'paintbrush-2', tags: ['color', 'design', 'draw'] },
  { name: 'pipette', tags: ['color', 'picker', 'eyedropper'] },
  { name: 'contrast', tags: ['color', 'brightness', 'theme'] },
  { name: 'sun-moon', tags: ['theme', 'dark', 'light'] },
  { name: 'scan', tags: ['qr', 'barcode', 'read'] },
  { name: 'qr-code', tags: ['scan', 'barcode', 'link'] },
  { name: 'fingerprint', tags: ['security', 'identity', 'biometric'] },
  { name: 'bot', tags: ['robot', 'ai', 'automation'] },
  { name: 'brain', tags: ['ai', 'think', 'smart'] },
  { name: 'lightbulb', tags: ['idea', 'tip', 'hint'] },
  { name: 'lightbulb-off', tags: ['idea', 'disabled'] },
  { name: 'lamp', tags: ['light', 'desk', 'work'] },
  { name: 'flashlight', tags: ['light', 'torch', 'search'] },
  { name: 'sun-dim', tags: ['brightness', 'low'] },
  { name: 'sun-medium', tags: ['brightness', 'medium'] },
];

/**
 * Get icon count (curated list)
 */
export const lucideIconCount = lucideIcons.length;

/**
 * Search Lucide icons by name or tags
 */
export function searchLucideIcons(query: string): LucideIconInfo[] {
  const lowerQuery = query.toLowerCase();
  return lucideIcons.filter(icon =>
    icon.name.toLowerCase().includes(lowerQuery) ||
    icon.tags.some(tag => tag.toLowerCase().includes(lowerQuery))
  );
}
