// ============ SEARCHABLE SELECT ============
class SearchableSelect {
    constructor(selectElement) {
        this.select = selectElement;
        this.options = [];
        this.selectedValue = '';
        this.selectedText = '';
        
        this.init();
    }
    
    init() {
        // Ambil semua option
        const optionElements = this.select.querySelectorAll('option');
        this.options = Array.from(optionElements)
            .filter(opt => opt.value !== '') // Skip placeholder
            .map(opt => ({
                value: opt.value,
                text: opt.textContent.trim()
            }));
        
        // Simpan nilai default (placeholder)
        const placeholder = optionElements[0]?.textContent || '-- Pilih --';
        
        // Buat wrapper
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'searchable-select';
        this.select.parentNode.insertBefore(this.wrapper, this.select);
        
        // Sembunyikan select asli
        this.select.style.display = 'none';
        
        // Input search
        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.className = 'search-input';
        this.input.placeholder = placeholder;
        this.input.autocomplete = 'off';
        this.wrapper.appendChild(this.input);
        
        // Clear button
        this.clearBtn = document.createElement('button');
        this.clearBtn.type = 'button';
        this.clearBtn.className = 'clear-btn';
        this.clearBtn.textContent = '✕';
        this.clearBtn.onclick = (e) => {
            e.stopPropagation();
            this.clear();
        };
        this.wrapper.appendChild(this.clearBtn);
        
        // Dropdown list
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'dropdown-list';
        this.wrapper.appendChild(this.dropdown);
        
        // Events
        this.input.addEventListener('focus', () => this.showDropdown());
        this.input.addEventListener('input', () => this.filterOptions());
        this.input.addEventListener('keydown', (e) => this.handleKeyboard(e));
        
        // Tutup dropdown saat klik di luar
        document.addEventListener('click', (e) => {
            if (!this.wrapper.contains(e.target)) {
                this.hideDropdown();
            }
        });
    }
    
    showDropdown() {
        this.filterOptions();
        this.dropdown.classList.add('show');
    }
    
    hideDropdown() {
        this.dropdown.classList.remove('show');
    }
    
    filterOptions() {
        const query = this.input.value.toLowerCase();
        this.dropdown.innerHTML = '';
        
        const filtered = this.options.filter(opt =>
            opt.text.toLowerCase().includes(query)
        );
        
        if (filtered.length === 0) {
            const noResult = document.createElement('div');
            noResult.className = 'dropdown-item no-result';
            noResult.textContent = 'Tidak ditemukan';
            this.dropdown.appendChild(noResult);
        } else {
            filtered.forEach(opt => {
                const item = document.createElement('div');
                item.className = 'dropdown-item';
                if (opt.value === this.selectedValue) {
                    item.classList.add('selected');
                }
                item.textContent = opt.text;
                item.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    this.selectOption(opt);
                });
                this.dropdown.appendChild(item);
            });
        }
    }
    
    selectOption(opt) {
        this.selectedValue = opt.value;
        this.selectedText = opt.text;
        this.input.value = opt.text;
        this.select.value = opt.value;
        
        // Trigger change event pada select asli
        const event = new Event('change', { bubbles: true });
        this.select.dispatchEvent(event);
        
        this.clearBtn.classList.add('show');
        this.hideDropdown();
    }
    
    clear() {
        this.selectedValue = '';
        this.selectedText = '';
        this.input.value = '';
        this.select.value = '';
        this.clearBtn.classList.remove('show');
        this.input.focus();
        
        const event = new Event('change', { bubbles: true });
        this.select.dispatchEvent(event);
    }
    
    handleKeyboard(e) {
        const items = this.dropdown.querySelectorAll('.dropdown-item:not(.no-result)');
        const current = this.dropdown.querySelector('.dropdown-item.highlighted');
        let index = Array.from(items).indexOf(current);
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            index = index < items.length - 1 ? index + 1 : 0;
            this.highlightItem(items, index);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            index = index > 0 ? index - 1 : items.length - 1;
            this.highlightItem(items, index);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (current) {
                current.click();
            }
        } else if (e.key === 'Escape') {
            this.hideDropdown();
        }
    }
    
    highlightItem(items, index) {
        items.forEach(i => i.classList.remove('highlighted'));
        if (items[index]) {
            items[index].classList.add('highlighted');
            items[index].scrollIntoView({ block: 'nearest' });
        }
    }
    
    setValue(value) {
        const opt = this.options.find(o => o.value === value);
        if (opt) {
            this.selectOption(opt);
        }
    }
}

// Inisialisasi semua searchable select
function initSearchableSelects() {
    const selects = document.querySelectorAll('.searchable');
    selects.forEach(select => {
        new SearchableSelect(select);
    });
}

// Jalankan saat halaman selesai load
document.addEventListener('DOMContentLoaded', initSearchableSelects);