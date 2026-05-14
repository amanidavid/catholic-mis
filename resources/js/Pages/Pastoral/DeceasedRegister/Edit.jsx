import DeceasedRegisterForm from './Form';

export default function DeceasedRegisterEdit({ entry }) {
    return (
        <DeceasedRegisterForm
            mode="edit"
            title="Edit Deceased Record"
            subtitle="Update death and funeral information."
            submitLabel="Update"
            submitRoute={route('pastoral.deceased-register.update', entry.uuid)}
            method="patch"
            initial={entry}
        />
    );
}
