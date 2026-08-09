from pathlib import Path

css_path = Path('public/assets/css/public-visual-system-v3.css')
css = css_path.read_text(encoding='utf-8')

old = '''.newsfeat-side,
.newsfeat-side__thumbs,
.newsfeat-side__texts {
    display: grid;
    gap: 0;
}
'''
new = '''.newsfeat-side,
.newsfeat-side__thumbs,
.newsfeat-side__texts {
    display: grid;
    gap: 0;
}

/* gov-theme.css previously used 2 columns for thumbnail news and 3 columns
   for text-only news. Visual System 3 turns each thumbnail item into its own
   horizontal mini-card, so those inherited parent tracks squeeze the title to
   a few characters per line. Keep the sidebar as one editorial vertical flow. */
.newsfeat-side__thumbs,
.newsfeat-side__texts {
    grid-template-columns: minmax(0, 1fr);
    width: 100%;
}
'''
if css.count(old) != 1:
    raise SystemExit(f'news side block occurrences: {css.count(old)}')
css = css.replace(old, new, 1)

old = '''.newsfeat-mini__thumb {
    overflow: hidden;
    border-radius: 3px;
}
'''
new = '''.newsfeat-mini__thumb {
    width: 100%;
    min-width: 0;
    min-height: 0;
    aspect-ratio: 4 / 3;
    overflow: hidden;
    border-radius: 3px;
}
'''
if css.count(old) != 1:
    raise SystemExit(f'news thumb block occurrences: {css.count(old)}')
css = css.replace(old, new, 1)

old = '''.newsfeat-mini__media {
    display: block;
    width: 100%;
    aspect-ratio: 4 / 3;
}
'''
new = '''.newsfeat-mini__media {
    display: block;
    width: 100%;
    height: 100%;
    min-height: 0;
    aspect-ratio: 4 / 3;
    object-fit: cover;
}
'''
if css.count(old) != 1:
    raise SystemExit(f'news media block occurrences: {css.count(old)}')
css = css.replace(old, new, 1)

old = '''.newsfeat-mini__body {
    align-self: center;
}
'''
new = '''.newsfeat-mini__body {
    min-width: 0;
    align-self: center;
}
'''
if css.count(old) != 1:
    raise SystemExit(f'news body block occurrences: {css.count(old)}')
css = css.replace(old, new, 1)

css_path.write_text(css, encoding='utf-8')

Path('.github/apply_news_feature_layout_fix.py').unlink()
Path('.github/workflows/apply-news-feature-layout-fix.yml').unlink()
