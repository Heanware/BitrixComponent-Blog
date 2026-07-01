document.addEventListener("DOMContentLoaded", () => {
    if(window.matchMedia("(max-width: 850px)").matches){
        document.addEventListener('click', (e) => {
            const aside = document.querySelector('.blog .main aside');
            const form = document.querySelector('.blog .main form');

            if(!aside) return;

            if(!aside.contains(e.target)){
                aside.classList.remove('nav-open');
            }

            if(form !== null && !form.contains(e.target)){
                form.classList.remove('search-open');
            }
        });

        document.querySelectorAll('.blog .main aside a').forEach(link => {
            link.addEventListener('click', (e) => {
                const aside = link.closest('aside');
                if(!aside) return;

                if(!aside.classList.contains('nav-open')){
                    e.preventDefault();
                    aside.classList.add('nav-open');
                }else{
                    aside.classList.remove('nav-open');
                }
            });
        });

        document.querySelectorAll('.js-articles-search').forEach(el => {
            el.addEventListener('click', () => {
                const form = el.closest('form');
                if(!form) return;

                form.classList.add('search-open');
                form.querySelector('input')?.focus();
            });
        });

        document.querySelectorAll('.articles-close-search').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                btn.closest('form')?.classList.remove('search-open');
            });
        });
    }

    new Swiper('.js-blog-swiper', {
        allowTouchMove: true,
        breakpoints: {
            991: {
                slidesPerView: 3,
                spaceBetween: 12,
            },
            850: {
                slidesPerView: 2,
                spaceBetween: 12,
            },

            0: {
                sliderPerView: 1,
                spaceBetween: 12,
            }
        },
        pagination: {},
    });

    const container = document.querySelector('.js-main-articles');
    const searchMessage = document.querySelector('.js-message');
    const pageCode = container.dataset.code;
    let loadMoreTrigger = document.getElementById('main__articles--trigger');
    let searching = false;
    let pageNum = 1;

    document.getElementById('blog-search').addEventListener(
        'input',
        debounce(search, 444)
    );

    function debounce(func, delay){
        let timeout;
        return function(...args){
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }

    async function search(event){
        searching = true;
        pageNum = 1;
        loadMoreTrigger.classList.remove('loading');
        observer.unobserve(loadMoreTrigger);
        container.classList.add('search-loading');

        fetchComponentAction('portfolio:blog', 'search', {
            query: event.target.value,
            code: pageCode,
        }).then(async(response) => {
            if(!response.data){
                console.error("Ошибка сервера!");
            }else{
                searchMessage.innerHTML = response.data.message || '';

                if(response.data.count > 0){
                    observer.unobserve(loadMoreTrigger);
                }else{
                    observer.observe(loadMoreTrigger);
                }

                container.innerHTML = response.data.items;
            }
        }).catch(error => {
            searchMessage.textContent = 'Ошибка отправки, попробуйте позже';
            console.error(error);
        }).finally(() => {
            searching = false;
            container.classList.remove('search-loading');
        });
    }

    const observer = new IntersectionObserver((entries) => {
        if(entries[0].isIntersecting && !loadMoreTrigger.classList.contains('loading') && !searching){
            loadMore();
        }
    }, {rootMargin: '100px'});

    function loadMore(){
        loadMoreTrigger.classList.add('loading');
        fetchComponentAction('portfolio:blog', 'loadMore', {
            code: pageCode,
            pageNum: pageNum
        }).then(response => {
            pageNum++;
            if(response.data.length){
                container.insertAdjacentHTML('beforeend', response.data);
                loadMoreTrigger.classList.remove('loading');
            }else{
                observer.unobserve(loadMoreTrigger);
            }
        });
    }

    observer.observe(loadMoreTrigger);
});

