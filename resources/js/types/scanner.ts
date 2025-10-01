export interface GiftCard {
    id: string;
    legacy_id: string;
    user: {
        name: string;
        avatar?: string | null;
    } | null;
    balance: number;
    status: boolean;
    expiry_date: string | null;
    qr_image_path?: string | null;
}

export interface DebitFormData {
    amount: number;
    reference: string;
    description?: string;
}

export interface Transaction {
    id: number;
    folio: string;
    gift_card: GiftCard;
    amount: number;
    balance_before: number;
    balance_after: number;
    reference: string;
    description: string;
    created_at: string;
    branch_name: string;
    cashier_name: string;
}

export type ScannerMode = 'scanning' | 'viewing' | 'processing' | 'success';

export interface Branch {
    id: number;
    name: string;
}

export interface ScannerPageProps {
    branch: Branch;
    user: {
        id: number;
        name: string;
        email: string;
    };
}
