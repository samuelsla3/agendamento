(() => {
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const elementos = document.querySelectorAll('.ui-aluno [data-revelar], .ui-aluno .desenvolvedores h2, .ui-aluno .desenvolvedores-cartao');
    const observer = new IntersectionObserver((entradas) => {
        entradas.forEach(({ target, isIntersecting }) => {
            if (!isIntersecting) return;
            target.classList.remove('subida-pendente');
            observer.unobserve(target);
        });
    }, { threshold: 0, rootMargin: '0px 0px -24px 0px' });
    elementos.forEach((elemento) => {
        elemento.classList.add('subida-animada', 'subida-pendente');
        observer.observe(elemento);
    });
})();
