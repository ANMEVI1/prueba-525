// Sistema de Continuidad Institucional - Scripts de Interfaz

document.addEventListener('DOMContentLoaded', () => {
    // 1. Password visibility toggle
    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-password-toggle]');
        if (!toggle) return;

        const input = document.getElementById(toggle.dataset.passwordToggle);
        if (!(input instanceof HTMLInputElement)) return;

        const isVisible = input.type === 'password';
        input.type = isVisible ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(isVisible));
        toggle.setAttribute('aria-label', isVisible ? 'Ocultar contraseña' : 'Mostrar contraseña');

        const openIcon = toggle.querySelector('[data-eye-open]');
        const closedIcon = toggle.querySelector('[data-eye-closed]');
        if (openIcon) openIcon.classList.toggle('hidden', isVisible);
        if (closedIcon) closedIcon.classList.toggle('hidden', !isVisible);
    });

    // 2. Prevent double submission & show loading indicator
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        const button = form.querySelector('button[type="submit"]');
        if (!(button instanceof HTMLButtonElement) || button.disabled) return;

        // If the submit has a confirmation prompt that was cancelled, don't disable
        if (button.dataset.confirm && !confirm(button.dataset.confirm)) {
            event.preventDefault();
            return;
        }

        button.disabled = true;
        button.classList.add('cursor-wait', 'opacity-70');
        button.dataset.originalHtml = button.innerHTML;
        button.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 size-4 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span>Procesando...</span>
        `;
    });

    // 3. Mobile sidebar drawer toggler
    const sidebarToggle = document.getElementById('toggle-sidebar');
    const mobileSidebar = document.getElementById('mobile-sidebar');
    const closeSidebar = document.getElementById('close-sidebar');

    if (sidebarToggle && mobileSidebar) {
        sidebarToggle.addEventListener('click', () => {
            mobileSidebar.classList.remove('hidden');
        });
    }

    if (closeSidebar && mobileSidebar) {
        closeSidebar.addEventListener('click', () => {
            mobileSidebar.classList.add('hidden');
        });
    }

    // 4. Dismissible alerts
    document.querySelectorAll('[data-dismiss-target]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = document.querySelector(btn.dataset.dismissTarget);
            if (target) {
                target.style.transition = 'opacity 0.2s ease';
                target.style.opacity = '0';
                setTimeout(() => target.remove(), 200);
            }
        });
    });

    // 5. Accessible Modal System (open, close, backdrop click, ESC key)
    document.addEventListener('click', (event) => {
        // Open modal
        const openBtn = event.target.closest('[data-modal-open]');
        if (openBtn) {
            const targetSelector = openBtn.dataset.modalOpen;
            const modal = document.querySelector(targetSelector);
            if (modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 80);
                }
            }
            return;
        }

        // Close modal via button
        const closeBtn = event.target.closest('[data-modal-close]');
        if (closeBtn) {
            const modal = closeBtn.closest('.modal-container') || document.querySelector(closeBtn.dataset.modalClose);
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
            return;
        }

        // Close on clicking backdrop
        if (event.target.classList.contains('modal-backdrop')) {
            const modal = event.target.closest('.modal-container');
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }
    });

    // 6. Interactive Tab Switcher
    document.addEventListener('click', (event) => {
        const tabBtn = event.target.closest('[data-tab-button]');
        if (!tabBtn) return;

        const tabGroup = tabBtn.closest('[data-tab-group]');
        if (!tabGroup) return;

        const targetId = tabBtn.dataset.tabButton;
        const groupName = tabGroup.dataset.tabGroup;

        tabGroup.querySelectorAll('[data-tab-button]').forEach((btn) => {
            const isActive = btn === tabBtn;
            btn.classList.toggle('bg-brand-600', isActive);
            btn.classList.toggle('text-white', isActive);
            btn.classList.toggle('shadow-xs', isActive);
            btn.classList.toggle('text-slate-600', !isActive);
            btn.classList.toggle('hover:bg-slate-100', !isActive);
            btn.classList.toggle('hover:text-slate-900', !isActive);
            btn.setAttribute('aria-selected', String(isActive));
        });

        document.querySelectorAll(`[data-tab-panel="${groupName}"]`).forEach((panel) => {
            const isMatch = panel.id === targetId;
            panel.classList.toggle('hidden', !isMatch);
        });
    });

    // 7. Native OS Folder Browser & Interactive Drive Selector
    function applyFolderDestination(path) {
        if (!path) return;
        const mainInput = document.getElementById('form_storage_destination');
        const modalInput = document.getElementById('modal_custom_path_input');

        if (mainInput instanceof HTMLInputElement) {
            mainInput.value = path;
            mainInput.classList.add('bg-emerald-50', 'ring-2', 'ring-emerald-500');
            setTimeout(() => {
                mainInput.classList.remove('bg-emerald-50', 'ring-2', 'ring-emerald-500');
            }, 2000);
        }

        if (modalInput instanceof HTMLInputElement) {
            modalInput.value = path;
        }

        const statusEl = document.getElementById('folder-picker-status');
        const msgEl = document.getElementById('folder-picker-message');
        if (statusEl && msgEl) {
            msgEl.textContent = `Carpeta asignada: ${path}`;
            statusEl.classList.remove('hidden');
            setTimeout(() => statusEl.classList.add('hidden'), 5000);
        }

        // Close modal if open
        const modal = document.getElementById('modal-folder-picker');
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }

    document.addEventListener('click', async (event) => {
        // Folder preset chips in main form
        const presetChip = event.target.closest('[data-preset-folder]');
        if (presetChip) {
            applyFolderDestination(presetChip.dataset.presetFolder);
            return;
        }

        // Quick drive destination chips inside modal
        const setDestBtn = event.target.closest('[data-set-destination]');
        if (setDestBtn) {
            applyFolderDestination(setDestBtn.dataset.setDestination);
            return;
        }

        // Apply custom path button inside modal
        const applyModalBtn = event.target.closest('#btn-apply-modal-path');
        if (applyModalBtn) {
            const modalInput = document.getElementById('modal_custom_path_input');
            if (modalInput instanceof HTMLInputElement && modalInput.value.trim() !== '') {
                applyFolderDestination(modalInput.value.trim());
            }
            return;
        }

        // Native Windows folder browser button (PowerShell WinForms Dialog)
        const nativeBtn = event.target.closest('[data-action="browse-folder-native"]');
        if (nativeBtn) {
            const currentPath = (document.getElementById('form_storage_destination') instanceof HTMLInputElement)
                ? document.getElementById('form_storage_destination').value
                : '';

            nativeBtn.dataset.originalHtml = nativeBtn.innerHTML;
            nativeBtn.disabled = true;
            nativeBtn.classList.add('cursor-wait', 'opacity-70');
            nativeBtn.innerHTML = `
                <div class="size-10 rounded-lg bg-brand-600 text-white grid place-items-center shrink-0">
                    <svg class="animate-spin size-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-brand-950">Abriendo ventana...</p>
                    <p class="text-[11px] text-brand-700">Revisa la ventana del explorador</p>
                </div>
            `;

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            fetch('/backups/browse-folder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ current_path: currentPath }),
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success && data.path) {
                        applyFolderDestination(data.path);
                    }
                })
                .catch((err) => {
                    console.error('Error al explorar carpetas:', err);
                })
                .finally(() => {
                    nativeBtn.disabled = false;
                    nativeBtn.classList.remove('cursor-wait', 'opacity-70');
                    if (nativeBtn.dataset.originalHtml) {
                        nativeBtn.innerHTML = nativeBtn.dataset.originalHtml;
                    }
                });
            return;
        }

        // HTML5 Browser Directory Picker (direct Chromium File System Access API)
        const html5Btn = event.target.closest('[data-action="browse-folder-html5"]');
        if (html5Btn) {
            if (typeof window.showDirectoryPicker === 'function') {
                try {
                    const dirHandle = await window.showDirectoryPicker({ mode: 'read' });
                    if (dirHandle && dirHandle.name) {
                        // Detect drive preference or construct path
                        const currentVal = (document.getElementById('form_storage_destination') instanceof HTMLInputElement)
                            ? document.getElementById('form_storage_destination').value
                            : 'D:\\Respaldos_TI';
                        const drivePrefix = currentVal.includes(':') ? currentVal.substring(0, 3) : 'D:\\';
                        applyFolderDestination(`${drivePrefix}${dirHandle.name}`);
                    }
                } catch {
                    // Dialog cancelled by user
                }
            } else {
                alert('Tu navegador no soporta el selector web directo. Utiliza la opción de Explorador de Windows o las unidades de la lista.');
            }
        }
    });

    // 8. Adaptive Schedule Frequency & Time inputs
    const freqSelect = document.getElementById('form_frequency');
    const timeInput = document.getElementById('form_time');
    const timeNotice = document.getElementById('time_interval_notice');

    function updateTimeInputState() {
        if (!freqSelect || !timeInput) return;
        const isDaily = freqSelect.value === 'daily';

        timeInput.disabled = !isDaily;
        timeInput.classList.toggle('opacity-40', !isDaily);
        timeInput.classList.toggle('bg-slate-100', !isDaily);
        timeInput.classList.toggle('cursor-not-allowed', !isDaily);

        if (timeNotice) {
            timeNotice.classList.toggle('hidden', isDaily);
        }
    }

    if (freqSelect) {
        freqSelect.addEventListener('change', updateTimeInputState);
        updateTimeInputState();
    }

    // Close on Escape key
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal-container:not(.hidden)').forEach((modal) => {
                modal.classList.add('hidden');
            });
            document.body.classList.remove('overflow-hidden');
        }
    });
});
