import type { PaginatedResponse } from '@/shared/api';

export interface Review {
    id: number;
    author_name: string;
    avatar_url: string | null;
    rating: number;
    text: string | null;
    published_at: string;
}

export type ReviewsResponse = PaginatedResponse<Review>;

export interface ParseEvent {
    id: number;
    type: string;
    payload: Record<string, unknown> | null;
    created_at: string;
}
