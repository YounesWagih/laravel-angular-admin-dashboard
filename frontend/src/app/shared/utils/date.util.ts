const mediumDateFormatter = new Intl.DateTimeFormat('en-EG', { dateStyle: 'medium' });
const mediumDateTimeFormatter = new Intl.DateTimeFormat('en-EG', {
  dateStyle: 'medium',
  timeStyle: 'short'
});

export function formatDate(value: string): string {
  return mediumDateFormatter.format(new Date(value));
}

export function formatDateTime(value: string): string {
  return mediumDateTimeFormatter.format(new Date(value));
}
