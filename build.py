"""Build content/articles/*.html from content/src/*.txt.
Syntax in source:
  {{kjv:Acts 8:1-3}}            -> blockquote with KJV text (fetched live, cached) + footnote "Acts 8:1–3 (KJV)."
  [[fn: footnote text]]         -> numbered footnote marker; text collected at end
  <q>…</q>                      -> block quotation (as given)
  ## Heading                    -> <h2>
  blank line separates paragraphs; lines starting with '>' are blockquote paragraphs
"""
import re,sys,json,os,urllib.request,urllib.parse,html
BASE=os.path.dirname(os.path.abspath(__file__))
CACHE=os.path.join(BASE,'kjv-cache.json')
cache=json.load(open(CACHE)) if os.path.exists(CACHE) else {}
def kjv(ref):
    if ref in cache: return cache[ref]
    u='https://bible-api.com/'+urllib.parse.quote(ref)+'?translation=kjv'
    d=json.load(urllib.request.urlopen(u,timeout=30))
    verses=[(v['verse'],' '.join(v['text'].split())) for v in d['verses']]
    cache[ref]=verses; json.dump(cache,open(CACHE,'w')); return verses
ABBR=('cf.','c.','St.','pp.','p.','vol.','no.','ed.','trans.','e.g.','i.e.','al.','Dr.','Mr.','Rev.','ibid.','op. cit.','fol.','fols.','v.','vv.','viz.','ch.')
def split_para(t, maxn=3):
    # sentence boundaries: . ? ! followed by space and a capital/quote/ʿ; avoid abbreviations and markup
    idx=[]
    for m in re.finditer(r'[.?!](?:"|”)?(?:<sup>.*?</sup>)?\s+(?=[A-Z"“ʿ(<]|al-)',t):
        pre=t[:m.start()+1]
        last=re.sub(r'<[^>]+>','',pre).split()[-1] if pre.split() else ''
        if last in ABBR or pre.rstrip().endswith('op. cit.'): continue
        if re.search(r'\b[A-Z]\.$',pre): continue
        if pre.count('<sup>')!=pre.count('</sup>'): continue
        if pre.count('<em>')!=pre.count('</em>'): continue
        idx.append(m.end())
    n=len(idx)+1
    if n<=maxn: return [t]
    # split into groups of <=3 sentences, balanced
    groups=(n+maxn-1)//maxn; per=(n+groups-1)//groups
    cuts=[idx[i-1] for i in range(per,n,per)]
    out=[]; last=0
    for c in cuts: out.append(t[last:c].strip()); last=c
    out.append(t[last:].strip()); return [x for x in out if x]
SBL={'Matthew':'Matt','Mark':'Mark','Luke':'Luke','John':'John','Acts':'Acts','Romans':'Rom','1 Corinthians':'1Cor','2 Corinthians':'2Cor','Galatians':'Gal','Ephesians':'Eph','Philippians':'Phil','Colossians':'Col','1 Thessalonians':'1Thess','2 Thessalonians':'2Thess','1 Timothy':'1Tim','2 Timothy':'2Tim','Titus':'Titus','Philemon':'Phlm','Hebrews':'Heb','James':'Jas','1 Peter':'1Pet','2 Peter':'2Pet','Revelation':'Rev'}
_sbl_cache={}
def greek(ref):
    """SBLGNT text for a NT reference like 'Acts 9:3-9'; None for OT refs."""
    m=re.match(r'^(.+?) (\d+):(\d+)(?:-(\d+))?$',ref.strip())
    if not m or m.group(1) not in SBL: return None
    book=SBL[m.group(1)]; ch=int(m.group(2)); a=int(m.group(3)); b=int(m.group(4) or a)
    if book not in _sbl_cache:
        d={}
        for line in open(os.path.join(BASE,'sblgnt',f'{book}.txt'),encoding='utf-8-sig'):
            if '\t' in line:
                k,t=line.rstrip('\n').split('\t',1); d[k.split(' ',1)[1]]=t
        _sbl_cache[book]=d
    out=[]
    for v in range(a,b+1):
        t=_sbl_cache[book].get(f'{ch}:{v}')
        if t is None: return None
        t=re.sub(r'[⸀⸁⸂⸃⸄⸅⸆⸇]','',t).strip()
        out.append((v,t))
    return out
URL_RE = re.compile(r'https?://[^\s<>"\']+')

def linkify(html):
    """Wrap every bare web address in a link. Addresses already inside a tag
    (an href or src) or inside a link's text are left alone; trailing
    sentence punctuation stays outside the link."""
    out, pos = [], 0
    for m in re.finditer(r'<a\b[^>]*>.*?</a>|<[^>]+>', html, flags=re.S):
        out.append(_link_text(html[pos:m.start()])); out.append(m.group(0)); pos = m.end()
    out.append(_link_text(html[pos:]))
    return ''.join(out)

def _link_text(text):
    def rep(m):
        url = m.group(0); tail = ''
        while url and url[-1] in '.,;:)]':
            tail = url[-1] + tail; url = url[:-1]
        return f'<a href="{url}" rel="noopener" target="_blank">{url}</a>{tail}'
    return URL_RE.sub(rep, text)

def build(src,dst,prefix):
    t=open(src).read()
    notes=[]
    def fn(ref_text):
        notes.append(ref_text.strip()); n=len(notes)
        return f'<sup><a href="#fn{prefix}-{n}" id="ref{prefix}-{n}">{n}</a></sup>'
    def kj(ref):
        ref=ref.strip(); vs=kjv(ref)
        body=' '.join(f'<span class="v">{n}</span>{tx}' for n,tx in vs)
        gk=greek(ref)
        if gk:
            gbody=' '.join(f'<span class="v">{n}</span>{tx}' for n,tx in gk)
            notes.append(ref.replace('-','–')+' (SBLGNT; KJV).'); n=len(notes)
            return f'\n\n>>>{gbody}||{body}<sup><a href="#fn{prefix}-{n}" id="ref{prefix}-{n}">{n}</a></sup>\n\n'
        notes.append(ref.replace('-','–')+' (KJV).'); n=len(notes)
        return f'\n\n>>{body}<sup><a href="#fn{prefix}-{n}" id="ref{prefix}-{n}">{n}</a></sup>\n\n'
    def marker(m):
        # {{kjv:...}} and [[fn:...]] are substituted together, in one
        # left-to-right pass, so note numbers follow true reading order.
        # Substituting each kind in its own separate pass (the previous
        # approach) numbers every {{kjv:}} before any [[fn:]], regardless
        # of which actually comes first in the source text.
        if m.group(1) is not None:
            return kj(m.group(1))
        return fn(m.group(2))
    t=re.sub(r'\{\{chart:([a-z0-9-]+)\}\}',lambda m: '\n\n<!--fig-->[paulus_chart id="'+m.group(1)+'"]\n\n',t)
    t=re.sub(r'\{\{fig:([a-z0-9-]+)(\|wide)?\|(.*?)\}\}',lambda m: '\n\n<!--fig-->[paulus_figure name="'+m.group(1)+'"'+(' wide="1"' if m.group(2) else '')+']'+m.group(3)+'[/paulus_figure]\n\n',t,flags=re.S)
    t=re.sub(r'\{\{kjv:([^}]+)\}\}|\[\[fn:(.*?)\]\]',marker,t,flags=re.S)
    out=[]
    for block in re.split(r'\n\s*\n',t.strip()):
        b=block.strip()
        if not b: continue
        if b.startswith('## '): out.append(f'<h2>{b[3:].strip()}</h2>')
        elif b.startswith('>>>'):
            g,e=b[3:].split('||',1); out.append(f'<blockquote class="scripture"><p class="greek" lang="grc">{g.strip()}</p><p>{e.strip()}</p></blockquote>')
        elif b.startswith('>>'): out.append(f'<blockquote class="scripture"><p>{b[2:].strip()}</p></blockquote>')
        elif b.startswith('>'):
            paras=[re.sub(r'^>\s?','',l) for l in b.split('\n')]
            out.append('<blockquote>'+''.join(f'<p>{p}</p>' for p in ' '.join(paras).split('\n')) +'</blockquote>') if False else out.append('<blockquote><p>'+' '.join(paras)+'</p></blockquote>')
        elif b.startswith('<!--fig-->'): out.append(b[len('<!--fig-->'):])
        elif b.startswith('<table'): out.append('<div class="paulus-table-wrap">'+b+'</div>')
        elif b.startswith('<'): out.append(b)
        else:
            for chunk in split_para(' '.join(b.split('\n'))): out.append('<p>'+chunk+'</p>')
    if notes:
        out.append('<ol class="footnotes">'+''.join(f'<li id="fn{prefix}-{i+1}">{n} <a href="#ref{prefix}-{i+1}" aria-label="Back to text">↩</a></li>' for i,n in enumerate(notes))+'</ol>')
    open(dst,'w').write(linkify('\n\n'.join(out))+'\n')
    return len(notes)
if __name__=='__main__':
    import glob
    src_dir=os.path.join(BASE,'content','src')
    out_dir=os.path.join(BASE,'content','articles')
    files=sorted(glob.glob(os.path.join(src_dir,'*.txt')))
    if not files:
        sys.exit(f'No source files found in {src_dir} — check the checkout path.')
    for src in files:
        name=os.path.basename(src)[:-4]
        n=build(src,os.path.join(out_dir,f'{name}.html'),name.split('-')[0])
        print(name,n,'notes')
