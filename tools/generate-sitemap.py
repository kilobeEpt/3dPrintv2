#!/usr/bin/env python3
"""
Sitemap Generator for 3D Print Pro

Генерирует sitemap.xml для статического сайта с учетом всех публичных страниц и якорей
Запуск: python3 tools/generate-sitemap.py [base_url]

@version 1.0.0
"""

import os
import sys
from datetime import datetime
from xml.etree.ElementTree import Element, SubElement, tostring
from xml.dom import minidom

def generate_sitemap(base_url='https://3dprint-omsk.ru'):
    """Генерирует sitemap.xml"""
    
    base_url = base_url.rstrip('/')
    
    # Определяем структуру сайта
    urls = [
        # Главная страница (с максимальным приоритетом)
        {
            'loc': f'{base_url}/',
            'changefreq': 'daily',
            'priority': '1.0'
        },
        # Основные разделы через якоря
        {
            'loc': f'{base_url}/#home',
            'changefreq': 'daily',
            'priority': '1.0'
        },
        {
            'loc': f'{base_url}/#services',
            'changefreq': 'weekly',
            'priority': '0.9'
        },
        {
            'loc': f'{base_url}/#calculator',
            'changefreq': 'weekly',
            'priority': '0.8'
        },
        {
            'loc': f'{base_url}/#portfolio',
            'changefreq': 'weekly',
            'priority': '0.9'
        },
        {
            'loc': f'{base_url}/#about',
            'changefreq': 'monthly',
            'priority': '0.7'
        },
        {
            'loc': f'{base_url}/#contact',
            'changefreq': 'monthly',
            'priority': '0.8'
        },
    ]
    
    # Проверка наличия файла index.html для определения даты последнего изменения
    script_dir = os.path.dirname(os.path.abspath(__file__))
    project_root = os.path.dirname(script_dir)
    index_file = os.path.join(project_root, 'index.html')
    
    if os.path.exists(index_file):
        last_modified = datetime.fromtimestamp(os.path.getmtime(index_file)).strftime('%Y-%m-%d')
    else:
        last_modified = datetime.now().strftime('%Y-%m-%d')
    
    # Добавляем lastmod ко всем URL
    for url in urls:
        url['lastmod'] = last_modified
    
    # Генерация XML
    urlset = Element('urlset')
    urlset.set('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9')
    
    for url_data in urls:
        url = SubElement(urlset, 'url')
        
        loc = SubElement(url, 'loc')
        loc.text = url_data['loc']
        
        if 'lastmod' in url_data:
            lastmod = SubElement(url, 'lastmod')
            lastmod.text = url_data['lastmod']
        
        if 'changefreq' in url_data:
            changefreq = SubElement(url, 'changefreq')
            changefreq.text = url_data['changefreq']
        
        if 'priority' in url_data:
            priority = SubElement(url, 'priority')
            priority.text = url_data['priority']
    
    # Форматирование XML
    xml_str = minidom.parseString(tostring(urlset)).toprettyxml(indent='  ')
    
    # Сохранение файла
    output_file = os.path.join(project_root, 'sitemap.xml')
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(xml_str)
    
    # Вывод информации
    print('✅ Sitemap generated successfully!')
    print('─────────────────────────────────────')
    print(f'Base URL: {base_url}')
    print(f'Output file: {output_file}')
    print(f'Total URLs: {len(urls)}')
    print(f'Last modified: {last_modified}')
    print()
    print('📋 Generated URLs:')
    for url_data in urls:
        print(f'  - {url_data["loc"]} (priority: {url_data["priority"]})')
    print()
    print('🔍 Validation:')
    print('  - Online: https://www.xml-sitemaps.com/validate-xml-sitemap.html')
    print('  - Google: https://search.google.com/search-console')
    print('  - Yandex: https://webmaster.yandex.ru/')
    print()

if __name__ == '__main__':
    base_url = sys.argv[1] if len(sys.argv) > 1 else 'https://3dprint-omsk.ru'
    generate_sitemap(base_url)
