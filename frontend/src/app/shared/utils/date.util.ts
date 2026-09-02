function currentLocale(): string {
  return document.documentElement.lang === 'ar' ? 'ar-EG' : 'en-EG';
}

export function formatDate(value: string): string {
  return new Intl.DateTimeFormat(currentLocale(), {
    dateStyle: 'medium',
  }).format(new Date(value));
}

export function formatDateTime(value: string): string {
  return new Intl.DateTimeFormat(currentLocale(), {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value));
}

export function formatCurrency(value: string | number): string {
  return new Intl.NumberFormat(currentLocale(), {
    style: 'currency',
    currency: 'EGP',
  }).format(Number(value));
}
