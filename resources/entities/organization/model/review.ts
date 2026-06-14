export interface Review {
    id: number;
    author_name: string;
    avatar_url: string | null;
    rating: number;
    text: string | null;
}

export interface ReviewsResponse {
    data: Review[];
    meta: {
        current_page: number;
        total: number;
        per_page: number;
    };
}

export interface ParseEvent {
    id: number;
    type: string;
    payload: Record<string, unknown> | null;
    created_at: string;
}
