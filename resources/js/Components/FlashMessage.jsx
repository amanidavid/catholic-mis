import { useEffect, useRef, useState } from 'react';

export default function FlashMessage({
    message,
    type = 'success',
    // If duration is null/undefined, the message will stay until user closes it.
    duration = null,
    onClose,
}) {
    const [visible, setVisible] = useState(!!message);
    const [hovered, setHovered] = useState(false);
    const timerRef = useRef(null);

    useEffect(() => {
        if (!message) return;

        setVisible(true);

        const startTimer = () => {
            if (duration == null) return;
            if (timerRef.current) clearTimeout(timerRef.current);
            timerRef.current = setTimeout(() => {
                setVisible(false);
            }, duration);
        };

        if (!hovered) {
            startTimer();
        }

        return () => {
            if (timerRef.current) clearTimeout(timerRef.current);
        };
    }, [message, hovered]);

    if (!message || !visible) return null;

    const styles =
        type === 'error'
            ? 'border-red-200 bg-red-50 text-red-700'
            : 'border-green-200 bg-green-50 text-green-700';

    return (
        <div
            className={`w-full rounded-xl border p-4 text-sm shadow-sm ${styles}`}
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="font-semibold">
                    {type === 'error' ? 'Error' : 'Success'}
                </div>
                <button
                    type="button"
                    onClick={() => {
                        setVisible(false);
                        onClose?.();
                    }}
                    className="text-xs font-semibold opacity-70 hover:opacity-100"
                >
                    Close
                </button>
            </div>
            <div className="mt-1 text-sm">{message}</div>
        </div>
    );
}
