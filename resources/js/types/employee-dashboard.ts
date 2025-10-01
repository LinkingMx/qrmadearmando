export interface EmployeeGiftCard {
    id: string;
    legacy_id: string;
    balance: number;
    status: boolean;
    expiry_date: string | null;
    qr_image_path: string | null;
    user: {
        name: string;
        email: string;
        avatar: string | null;
    };
}

export interface EmployeeTransaction {
    id: number;
    created_at: string;
    type: 'credit' | 'debit' | 'adjustment';
    type_label: string;
    amount: number;
    balance_after: number;
    branch_name: string;
    description: string;
}

export interface TransactionsPagination {
    data: EmployeeTransaction[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
}

export interface EmployeeDashboardProps {
    giftCard?: EmployeeGiftCard;
}
