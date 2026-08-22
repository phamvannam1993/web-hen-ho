<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Tin tức / cẩm nang hẹn hò. */
class Blog extends MY_Controller
{
    private $per_page = 9;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('m_article');
    }

    public function index($page = 1)
    {
        $cat_slug = $this->input->get('chuyen-muc');
        $cat = $cat_slug ? $this->m_category->by_slug($cat_slug, 'blog') : null;

        $page   = max(1, (int) $page);
        $total  = $this->m_article->count_published($cat['id'] ?? null);

        $this->render('blog/index', array(
            'title'      => $cat ? $cat['name'] : 'Cẩm nang hẹn hò',
            'articles'   => $this->m_article->published($this->per_page, ($page - 1) * $this->per_page, $cat['id'] ?? null),
            'total'      => $total,
            'pagination' => pagination_links('tin-tuc', $page, $total, $this->per_page, $this->input->get()),
            'blog_cats'  => $this->m_category->all('blog'),
            'current_cat' => $cat,
        ));
    }

    public function detail($slug)
    {
        $article = $this->m_article->by_slug($slug);
        if (!$article || $article['status'] !== 'published') {
            show_404();
        }
        $this->m_article->increase_view($article['id']);

        $this->render('blog/detail', array(
            'title'      => $article['title'],
            'meta_desc'  => $article['seo_desc'] ?: excerpt($article['excerpt'] ?: $article['content'], 160),
            'article'    => $article,
            'related'    => $this->m_article->published(4, 0, $article['category_id']),
            'blog_cats'  => $this->m_category->all('blog'),
        ));
    }
}
