export function asset(path = '') {
  const basePath = window?.LaravelApp?.assetUrl ?? '';

  console.log('BASE PATH: ' + basePath);

  try {
    if (!path) return basePath;

    const cleanBase = basePath.replace(/\/$/, '');
    const cleanPath = String(path).replace(/^\//, '');

    return new URL(cleanPath, `${cleanBase}/`).toString();
  } catch {
    return basePath;
  }
}
