export const orgKeys = {
    all: ['organizations'] as const,
    lists: () => [...orgKeys.all, 'list'] as const,
    details: () => [...orgKeys.all, 'detail'] as const,
    detail: (id: number) => [...orgKeys.details(), id] as const,
    reviews: (id: number) => [...orgKeys.all, 'reviews', id] as const,
};
