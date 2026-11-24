from pathlib import Path
import hashlib,re
R=Path(__file__).resolve().parents[1]
fuentes=R/'resources/css';comunes=['header.css','diseno.css','funciones.css','calidad.css','operacion.css','agenda-integral.css']
(R/'public/css/base.css').write_text((fuentes/'styles.css').read_text(encoding='utf-8'),encoding='utf-8')
(R/'public/css/componentes.css').write_text('\n'.join('/* '+n+' */\n'+(fuentes/n).read_text(encoding='utf-8') for n in comunes),encoding='utf-8')
for f in R.glob('app/**/*.php'):
    s=f.read_text(encoding='utf-8')
    def version(m):
        p=R/'public'/m[1];return m[1]+'?v='+hashlib.sha256(p.read_bytes()).hexdigest()[:10] if p.is_file() else m[0]
    s=re.sub(r'((?:css|js)/[a-zA-Z0-9_.-]+\.(?:css|js))(?:\?v=[a-zA-Z0-9]+)?',version,s)
    # Conserva el recurso original y añade sus variantes locales.
    def responsive(m):
        ruta=m[1];name=Path(ruta).stem
        if not (R/'public/imagenes/responsive'/f'{name}-480.webp').is_file():return m[0]
        return f'src="{ruta}" srcset="imagenes/responsive/{name}-480.webp 480w, imagenes/responsive/{name}-800.webp 800w, {ruta} 1200w" sizes="(max-width: 650px) 92vw, (max-width: 1100px) 48vw, 600px"'
    s=re.sub(r'src="(imagenes/(?:fotografias|propias)/[a-z-]+\.webp)"(?! srcset)',responsive,s)
    f.write_text(s,encoding='utf-8')
print('Estilos y versiones de recursos construidos')

