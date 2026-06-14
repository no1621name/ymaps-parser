import { client } from '@/shared/api';
import type { ReviewsResponse } from '../model/review';

export async function fetchReviews(id: number, page: number, perPage: number = 50): Promise<ReviewsResponse> {
    return client<ReviewsResponse>(`/api/organizations/${id}/reviews`, {
        query: { page, per_page: perPage },
    });
}
