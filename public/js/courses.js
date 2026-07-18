let currentFilter = 'all';

function setFilter(el, filter) {
    currentFilter = filter;
    document.querySelectorAll('.filter-tag').forEach(t => t.classList.remove('filter-tag-active'));
    el.classList.add('filter-tag-active');
    filterCourses();
}

function filterCourses() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const cards  = document.querySelectorAll('.course-card');
    let visible  = 0;

    cards.forEach(card => {
        const matchFilter = currentFilter === 'all' || card.dataset.category.split(' ').includes(currentFilter);
        const matchSearch = card.dataset.title.includes(search);
        if (matchFilter && matchSearch) {
            card.style.display = '';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });

    document.getElementById('courses-count').textContent = visible + ' cours';
    document.getElementById('no-results').style.display  = visible === 0 ? 'flex' : 'none';
}
