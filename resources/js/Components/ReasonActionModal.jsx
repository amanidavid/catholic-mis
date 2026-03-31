import FloatingTextarea from '@/Components/FloatingTextarea';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';

export default function ReasonActionModal({
    show,
    onClose,
    title,
    subtitle,
    label = 'Reason',
    value,
    onChange,
    onSubmit,
    error,
    processing = false,
    actionLabel = 'Submit',
}) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <div className="p-6">
                <ModalHeader
                    title={title}
                    subtitle={subtitle}
                    onClose={onClose}
                    showRequiredNote
                />

                <form onSubmit={onSubmit} className="mt-4 space-y-4">
                    <FloatingTextarea
                        id="reason_action_modal_reason"
                        label={label}
                        value={value}
                        onChange={(event) => onChange(event.target.value)}
                        error={error}
                        required
                        rows={4}
                    />

                    <div className="flex items-center justify-end gap-2">
                        <SecondaryButton type="button" onClick={onClose} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton className="h-11 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700" disabled={processing}>
                            {processing && <Spinner size="sm" className="mr-2 text-white" />}
                            <span>{actionLabel}</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
