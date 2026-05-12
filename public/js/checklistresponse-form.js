document.querySelectorAll('.vs-checkbox-wrap').forEach((group) => {
    const options = Array.from(group.querySelectorAll('.vs-checkbox-option'));

    const syncSelection = () => {
        options.forEach((option) => {
            const input = option.querySelector('input[type="radio"]');
            option.classList.toggle('is-selected', Boolean(input && input.checked));
        });
    };

    options.forEach((option) => {
        option.addEventListener('click', () => {
            const input = option.querySelector('input[type="radio"]');
            if (input) {
                input.checked = true;
                syncSelection();
            }
        });
    });

    syncSelection();
});
