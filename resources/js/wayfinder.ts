export type RouteQueryOptions = Record<string, any> | undefined;

export interface RouteDefinition<T extends string = string> {
    url: string;
    method: T;
}

export interface RouteFormDefinition<T extends string = string> extends RouteDefinition<T> {
    form?: true;
}

export function queryParams(options?: RouteQueryOptions): string {
    if (!options || Object.keys(options).length === 0) {
        return '';
    }
    
    const params = new URLSearchParams();
    
    for (const [key, value] of Object.entries(options)) {
        if (value !== undefined && value !== null) {
            if (Array.isArray(value)) {
                value.forEach(v => params.append(`${key}[]`, String(v)));
            } else {
                params.append(key, String(value));
            }
        }
    }
    
    const searchString = params.toString();
    return searchString ? `?${searchString}` : '';
}

export function applyUrlDefaults(url: string, defaults?: RouteQueryOptions): string {
    if (!defaults || Object.keys(defaults).length === 0) {
        return url;
    }
    
    // If the URL already has query parameters, merge them with defaults
    const [basePath, existingQuery] = url.split('?');
    const existingParams = new URLSearchParams(existingQuery || '');
    
    // Apply defaults only for parameters that don't already exist
    for (const [key, value] of Object.entries(defaults)) {
        if (!existingParams.has(key) && value !== undefined && value !== null) {
            if (Array.isArray(value)) {
                value.forEach(v => existingParams.append(`${key}[]`, String(v)));
            } else {
                existingParams.append(key, String(value));
            }
        }
    }
    
    const queryString = existingParams.toString();
    return queryString ? `${basePath}?${queryString}` : basePath;
}

// Make functions available globally for generated routes
if (typeof window !== 'undefined') {
    (window as any).queryParams = queryParams;
    (window as any).applyUrlDefaults = applyUrlDefaults;
}
