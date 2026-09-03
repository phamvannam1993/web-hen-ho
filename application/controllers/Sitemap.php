<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sinh sitemap XML ngay lúc gọi, không lưu file tĩnh — thêm tỉnh hay đăng bài
 * mới là sitemap tự cập nhật theo.
 *
 *   /sitemap.xml          bảng mục lục trỏ sang 5 tệp con
 *   /sitemap-pages.xml    trang cố định và trang nội dung (/trang/...)
 *   /sitemap-dating.xml   /hen-ho và 4 nhóm nam, nu, gay, les
 *   /sitemap-tamsu.xml    /tam-su và 4 nhóm tương tự
 *   /sitemap-khuvuc.xml   /khu-vuc và toàn bộ tỉnh thành
 *   /sitemap-posts.xml    bài viết đã xuất bản
 */
class Sitemap extends MY_Controller
{
    /** Bốn nhóm dùng chung cho cả hẹn hò lẫn tâm sự. */
    private $nhom = array('nam', 'nu', 'gay', 'les');

    /** Bảng mục lục trỏ sang các sitemap con. */
    public function index()
    {
        $ten = array('pages', 'dating', 'tamsu', 'khuvuc', 'posts');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($ten as $t) {
            $xml .= "  <sitemap>\n"
                  . '    <loc>' . site_url('sitemap-' . $t . '.xml') . "</loc>\n"
                  . '    <lastmod>' . $this->moi_nhat($t) . "</lastmod>\n"
                  . "  </sitemap>\n";
        }
        $this->tra_ve($xml . '</sitemapindex>');
    }

    /** Trang cố định và các trang nội dung do khu quản trị tạo. */
    public function pages()
    {
        $urls = array(
            array('tin-tuc',     null, 'daily',   '0.8'),
            array('hen-ho',      null, 'daily',   '0.9'),
            array('tam-su',      null, 'daily',   '0.9'),
            array('thanh-vien',  null, 'always',  '0.7'),
            array('swipe-match', null, 'weekly',  '0.6'),
        );
        foreach ($this->db->select('slug, updated_at')->where('is_active', 1)
                     ->order_by('id')->get('pages')->result_array() as $p) {
            $urls[] = array('trang/' . $p['slug'], $p['updated_at'], 'monthly', '0.5');
        }
        $this->tra_ve($this->bo_url($urls));
    }

    /** /hen-ho và bốn nhóm. */
    public function dating()
    {
        $this->tra_ve($this->bo_url($this->trang_nhom('hen-ho')));
    }

    /** /tam-su và bốn nhóm. */
    public function tamsu()
    {
        $this->tra_ve($this->bo_url($this->trang_nhom('tam-su')));
    }

    /** Trang khu vực tổng và từng tỉnh thành. */
    public function khuvuc()
    {
        $urls = array(array('khu-vuc', null, 'weekly', '0.9'));
        foreach ($this->m_province->all() as $t) {
            $urls[] = array($t['slug'], null, 'daily', '0.8');
        }
        $this->tra_ve($this->bo_url($urls));
    }

    /** Bài viết đã xuất bản. */
    public function posts()
    {
        $urls = array();
        foreach ($this->db->select('slug, updated_at, published_at')
                     ->where('status', 'published')->order_by('id', 'DESC')
                     ->get('articles')->result_array() as $a) {
            $urls[] = array('tin-tuc/' . $a['slug'],
                $a['updated_at'] ?: $a['published_at'], 'monthly', '0.7');
        }
        $this->tra_ve($this->bo_url($urls));
    }

    /* ------------------------------ dùng chung ------------------------------ */

    /** Trang tổng + bốn nhóm của /hen-ho hoặc /tam-su. */
    private function trang_nhom($goc)
    {
        $urls = array(array($goc, null, 'daily', '0.9'));
        foreach ($this->nhom as $n) {
            $urls[] = array($goc . '/' . $n, null, 'daily', '0.8');
        }
        return $urls;
    }

    /** Dựng khối <urlset> từ mảng [đường dẫn, ngày sửa, tần suất, độ ưu tiên]. */
    private function bo_url($urls)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $ngay = !empty($u[1]) ? date('Y-m-d', strtotime($u[1])) : date('Y-m-d');
            $xml .= "  <url>\n"
                  . '    <loc>' . htmlspecialchars(site_url($u[0]), ENT_XML1) . "</loc>\n"
                  . '    <lastmod>' . $ngay . "</lastmod>\n"
                  . '    <changefreq>' . $u[2] . "</changefreq>\n"
                  . '    <priority>' . $u[3] . "</priority>\n"
                  . "  </url>\n";
        }
        return $xml . '</urlset>';
    }

    /** Ngày sửa gần nhất của từng nhóm, để bảng mục lục không báo ngày sai. */
    private function moi_nhat($nhom)
    {
        if ($nhom === 'posts') {
            $r = $this->db->select_max('updated_at', 'm')->where('status', 'published')
                     ->get('articles')->row_array();
            return !empty($r['m']) ? date('Y-m-d', strtotime($r['m'])) : date('Y-m-d');
        }
        if ($nhom === 'pages') {
            $r = $this->db->select_max('updated_at', 'm')->where('is_active', 1)
                     ->get('pages')->row_array();
            return !empty($r['m']) ? date('Y-m-d', strtotime($r['m'])) : date('Y-m-d');
        }
        return date('Y-m-d');
    }

    /** Trả nội dung XML kèm đúng kiểu nội dung. */
    private function tra_ve($xml)
    {
        $this->output->set_content_type('application/xml', 'utf-8')->set_output($xml);
    }
}
