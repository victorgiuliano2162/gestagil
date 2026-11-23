const PAYMENT_METHOD_LABELS: Record<string, string> = {
    pix: 'Pix',
    cash: 'Dinheiro',
    card: 'Cartão',
    other: 'Outro',
};

export function formatPaymentMethod(method: string | null | undefined): string {
    if (!method) return '-';
    return PAYMENT_METHOD_LABELS[method.toLowerCase()] ?? method;
}
