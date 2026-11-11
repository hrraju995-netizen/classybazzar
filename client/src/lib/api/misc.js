import { apiFetch } from './client';

export function getSettings() {
  return apiFetch('/settings', {
    cache: 'no-store',
  });
}

export async function getSettingsSafe() {
  try {
    return await getSettings();
  } catch {
    return {
      site: { name: 'Classybazar' },
      theme: {
        primaryColor: '#eb6e1b', secondaryColor: '#111827',
        buttonColor: '#eb6e1b', buttonHoverColor: '#111827', buttonTextColor: '#ffffff',
        badgeColor: '#ef4444',
        headerBackground: '#ffffff', headerTextColor: '#111827',
        menubarBackground: '#ffffff', menubarTextColor: '#111827',
        topbarBackground: '#111111', topbarTextColor: '#fafafa',
        footerBackground: '#050505', footerTextColor: '#ffffff',
        bodyBackground: '#ffffff', cardBackground: '#ffffff', textColor: '#111827',
        fontHeading: 'Poppins', fontBody: 'Inter',
      },
      header: { showTopbar: false, showSearch: true, sticky: true },
      footer: { social: [] },
      home: { slides: [], fixedCategorySidebar: false, categoryIds: [], sections: [], supportItems: [] },
      contact: {}, tracking: {}, menus: {},
      shipping: { inside: 70, outside: 130, freeAbove: 0 },
    };
  }
}

export function getPage(slug) {
  return apiFetch(`/pages/${slug}`, {
    cache: 'no-store',
  });
}

export function getPages() {
  return apiFetch('/pages', { cache: 'no-store' });
}

export function sendContactMessage(payload) {
  return apiFetch('/contact', { method: 'POST', body: payload });
}

export function getWishlist() {
  return apiFetch('/wishlist', { auth: true, cache: 'no-store' });
}

export function toggleWishlist(productId) {
  return apiFetch('/wishlist/toggle', { method: 'POST', auth: true, body: { product_id: productId } });
}

export function syncWishlist(productIds) {
  return apiFetch('/wishlist/sync', { method: 'POST', auth: true, body: { product_ids: productIds } });
}
