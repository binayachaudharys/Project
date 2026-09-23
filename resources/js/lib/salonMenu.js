export function categoryAnchor(title) {
    return String(title)
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}

export function packageCategoryHref(title) {
    return `${route('packages.index')}#${categoryAnchor(title)}`;
}
