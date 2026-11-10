import{r as c,j as e}from"./vendor-react-C5iM2OHa.js";import{d as G,L as v,u as U}from"./vendor-router-DMvM_U1d.js";import{a as T,q as Y,b as Q,m as W,z as X,c as ee,H as se,B as w,V as R,S as b,I as te,T as re,J as ae,e as oe,Y as ie,i as de,t as x}from"./index-katech-2026.js";import{u as L}from"./useProducts-DgslfEau.js";import{u as ne,a as ce}from"./reviews-DKh1xD6e.js";import{P as le}from"./ProductCard-B9bMVARI.js";import{u as me,I as xe}from"./ImageCatalogue-B9twUtZr.js";import"./vendor-radix-shell-BoYC3VWq.js";const ue={cpu:"spec.cpu",ram:"spec.ram",storage:"spec.storage",gpu:"spec.gpu",screen:"spec.screen",os:"spec.os",battery:"spec.battery",weight:"spec.weight",form:"spec.form",motherboard:"spec.motherboard",psu:"spec.psu",camera:"spec.camera"};function we(){const{id:t}=G(),d=L().find(r=>r.id===t),u=T()(d?.name)??"";return c.useEffect(()=>{document.title=d?`${u} — KA Tech`:"Produit introuvable — KA Tech"},[d,u]),d?e.jsx(pe,{p:d}):e.jsxs("div",{className:"container mx-auto max-w-xl px-4 py-16 text-center",children:[e.jsx("h1",{className:"text-2xl font-bold",children:"Produit introuvable"}),e.jsx(v,{to:"/category",className:"mt-4 inline-block text-primary hover:underline",children:"Voir le catalogue"})]})}
function ProductImageCarousel({images:S,title:T}){
  const[cur,setCur]=c.useState(0);
  const[tDelta,setTDelta]=c.useState(0);
  const[swiping,setSwiping]=c.useState(!1);
  const touchRef=c.useRef({startX:0,delta:0,active:!1});
  const curRef=c.useRef(cur);
  curRef.current=cur;
  const safeImgs=Array.isArray(S)&&S.length>0?S:[];
  const goTo=n=>{if(safeImgs.length<=1)return;setCur((n+safeImgs.length)%safeImgs.length)};
  const onTS=ev=>{if(safeImgs.length<=1)return;const x=ev.touches[0].clientX;touchRef.current={startX:x,delta:0,active:!0};setTDelta(0);setSwiping(!0)};
  const onTM=ev=>{if(!touchRef.current.active)return;const d=ev.touches[0].clientX-touchRef.current.startX;touchRef.current.delta=d;setTDelta(d)};
  const onTE=()=>{if(!touchRef.current.active)return;const d=touchRef.current.delta;touchRef.current.active=!1;if(d>35)goTo(curRef.current-1);else if(d<-35)goTo(curRef.current+1);setTDelta(0);setSwiping(!1)};
  const onKD=ev=>{if(ev.key==="ArrowLeft")goTo(curRef.current-1);else if(ev.key==="ArrowRight")goTo(curRef.current+1)};

  return e.jsxs("div",{
    className:"space-y-3.5 select-none","data-carousel":"product",
    children:[
      e.jsxs("div",{
        tabIndex:0,
        onKeyDown:onKD,
        onTouchStart:onTS,
        onTouchMove:onTM,
        onTouchEnd:onTE,
        className:"rounded-2xl border border-border bg-card relative overflow-hidden aspect-square group focus:outline-none focus:ring-2 focus:ring-primary/40 shadow-sm",
        children:[
          e.jsx("div",{
            className:"absolute inset-0 blur-3xl opacity-30 pointer-events-none",
            style:{background:"var(--gradient-glow)"},
            "aria-hidden":!0
          }),
          e.jsx("div",{
            className:"size-full flex",
            style:{
              transform:`translate3d(calc(-${cur*100}% + ${tDelta}px), 0, 0)`,
              transition:swiping?"none":"transform 320ms cubic-bezier(0.25, 1, 0.5, 1)",
              willChange:"transform"
            },
            children:safeImgs.map((img,idx)=>
              e.jsx("div",{
                className:"size-full shrink-0 flex items-center justify-center p-8 relative",
                children:e.jsx(xe,{
                  src:img,
                  alt:`${T} - photo ${idx+1}`,
                  width:1024,
                  height:1024,
                  loading:idx===cur?"eager":"lazy",
                  decoding:"async",
                  className:"size-full object-contain"
                })
              },idx)
            )
          }),
          safeImgs.length>1&&e.jsxs(e.Fragment,{
            children:[
              e.jsx("button",{
                type:"button",
                onClick:ev=>{ev.stopPropagation();goTo(cur-1)},
                "aria-label":"Image précédente","data-carousel-prev":true,
                className:"absolute left-3 top-1/2 -translate-y-1/2 size-10 rounded-full bg-background/80 hover:bg-background border border-border backdrop-blur-md shadow-md flex items-center justify-center transition-all z-10 text-foreground opacity-90 hover:opacity-100 hover:scale-105 active:scale-95 focus:outline-none",
                children:e.jsx("svg",{xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 24 24",fill:"none",stroke:"currentColor",strokeWidth:"2.5",strokeLinecap:"round",strokeLinejoin:"round",className:"size-5",children:e.jsx("path",{d:"m15 18-6-6 6-6"})})
              }),
              e.jsx("button",{
                type:"button",
                onClick:ev=>{ev.stopPropagation();goTo(cur+1)},
                "aria-label":"Image suivante","data-carousel-next":true,
                className:"absolute right-3 top-1/2 -translate-y-1/2 size-10 rounded-full bg-background/80 hover:bg-background border border-border backdrop-blur-md shadow-md flex items-center justify-center transition-all z-10 text-foreground opacity-90 hover:opacity-100 hover:scale-105 active:scale-95 focus:outline-none",
                children:e.jsx("svg",{xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24",viewBox:"0 0 24 24",fill:"none",stroke:"currentColor",strokeWidth:"2.5",strokeLinecap:"round",strokeLinejoin:"round",className:"size-5",children:e.jsx("path",{d:"m9 18 6-6-6-6"})})
              }),
              e.jsx("div",{
                className:"absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-background/80 backdrop-blur-md border border-border shadow-sm z-10",
                children:safeImgs.map((_,idx)=>
                  e.jsx("button",{
                    type:"button",
                    onClick:ev=>{ev.stopPropagation();goTo(idx)},
                    "aria-label":`Aller à l'image ${idx+1}`,"data-carousel-dot":idx,"aria-current":idx===cur?"true":"false",
                    className:`transition-all duration-200 rounded-full ${idx===cur?"w-5 h-2 bg-primary shadow-sm":"w-2 h-2 bg-muted-foreground/40 hover:bg-muted-foreground/70"}`
                  },idx)
                )
              }),
              e.jsxs("span",{
                className:"absolute top-3 right-3 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-background/80 backdrop-blur-md border border-border text-muted-foreground z-10",
                children:[cur+1," / ",safeImgs.length]
              })
            ]
          })
        ]
      }),
      safeImgs.length>1&&e.jsx("div",{
        className:"flex gap-2.5 overflow-x-auto pb-1 pt-0.5 no-scrollbar",
        children:safeImgs.map((img,idx)=>
          e.jsx("button",{
            type:"button",
            onClick:()=>goTo(idx),
            "aria-label":`Afficher image ${idx+1}`,"data-carousel-thumb":idx,"aria-current":idx===cur?"true":"false",
            className:`size-16 shrink-0 rounded-xl overflow-hidden border-2 transition-all p-1 bg-card ${idx===cur?"border-primary ring-2 ring-primary/30 scale-105":"border-border hover:border-muted-foreground/50 opacity-70 hover:opacity-100"}`,
            children:e.jsx(xe,{
              src:img,
              alt:`${T} miniature ${idx+1}`,
              width:100,
              height:100,
              loading:"lazy",
              decoding:"async",
              className:"size-full object-contain"
            })
          },idx)
        )
      })
    ]
  });
}

function pe({p:t}){const k=L(),d=Y[t.category],[l,u]=c.useState("specs"),{t:r}=Q(),j=T(),{user:m}=W(),{addToCart:M,toggleFavorite:D,isFavorite:E}=X(),N=U(),p=E(t.id),F=ee(k).filter(s=>s.category===t.category&&s.id!==t.id).slice(0,4),z=t.oldPrice?Math.round((t.oldPrice-t.price)/t.oldPrice*100):0,i=j(t.name)??"",g=j(t.specLine)??"",K=[{id:"specs",key:"pd.tab.specs"},{id:"desc",key:"pd.tab.desc"},{id:"rev",key:"pd.tab.rev"},{id:"ship",key:"pd.tab.ship"}],{data:h}=ne(t.id),{create:O}=ce(),a=c.useMemo(()=>{const s=h.length;return{avg:s?h.reduce((n,Z)=>n+Z.rating,0)/s:0,count:s,approved:h}},[h]),[C,P]=c.useState(5),[S,$]=c.useState(""),[y,A]=c.useState(""),[V,I]=c.useState(!1),_=async()=>{if(!m){N("/auth");return}await M(t.id),x.success(r("pd.added"),{description:i})},H=async()=>{if(!m){N("/auth");return}await D(t.id),x.success(p?"Retiré des favoris":"Ajouté aux favoris")},q=async s=>{if(s.preventDefault(),!m){N("/auth");return}if(!y.trim()){x.error("Écrivez un commentaire");return}try{await O(t.id,{productName:i,customerName:m.name??m.email??"Client",rating:C,title:S.trim()||"Avis client",body:y.trim()}),A(""),$(""),P(5),I(!0),x.success("Merci ! Votre avis sera publié après modération.")}catch(o){const n=o instanceof Error?o.message:"Erreur lors de l'envoi";x.error(n)}},B=c.useMemo(()=>{const s=[0,0,0,0,0];return a.approved.forEach(o=>{s[Math.min(4,Math.max(0,o.rating-1))]++}),s},[a.approved]),f=`${window.location.origin}/product/${t.id}`,J={"@context":"https://schema.org","@type":"Product",name:i,brand:{"@type":"Brand",name:t.brand},category:r(d.labelKey),image:me(t.image),description:g,url:f,offers:{"@type":"Offer",priceCurrency:"DZD",price:t.price,availability:t.stock==="out"?"https://schema.org/OutOfStock":"https://schema.org/InStock",url:f},aggregateRating:a.count>0?{"@type":"AggregateRating",ratingValue:a.avg.toFixed(1),reviewCount:a.count}:void 0};return e.jsxs("div",{className:"container-page py-8 lg:py-12",children:[e.jsxs(se,{children:[e.jsx("title",{children:`${i} — KA Tech`}),e.jsx("meta",{name:"description",content:`${i} — ${g}. ${w(t.price)}. Garantie officielle KA Tech.`}),e.jsx("link",{rel:"canonical",href:f}),e.jsx("meta",{property:"og:type",content:"product"}),e.jsx("meta",{property:"og:title",content:i}),e.jsx("meta",{property:"og:description",content:g}),e.jsx("meta",{property:"og:url",content:f}),e.jsx("script",{type:"application/ld+json",children:JSON.stringify(J)})]}),e.jsxs("nav",{className:"text-xs text-muted-foreground flex items-center gap-1.5 mb-6",children:[e.jsx(v,{to:"/",className:"hover:text-foreground",children:r("common.home")}),e.jsx(R,{className:"size-3"}),e.jsx(v,{to:`/category/${t.category}`,className:"hover:text-foreground",children:r(d.labelKey)}),e.jsx(R,{className:"size-3"}),e.jsx("span",{className:"text-foreground line-clamp-1",children:i})]}),e.jsxs("div",{className:"grid lg:grid-cols-2 gap-10",children:[e.jsx(ProductImageCarousel,{images:(Array.isArray(t.images)&&t.images.length>0)?t.images:(t.image?[t.image]:[]),title:i}),e.jsxs("div",{className:"space-y-5",children:[e.jsxs("div",{className:"flex items-center gap-2",children:[e.jsx("span",{className:"inline-flex items-center px-2.5 py-1 rounded text-xs font-semibold bg-card border border-border",children:t.brand}),z>0&&e.jsxs("span",{className:"inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-destructive text-destructive-foreground",children:["-",z,"%"]})]}),e.jsx("h1",{className:"text-3xl lg:text-4xl font-bold leading-tight",children:i}),e.jsx("p",{className:"text-muted-foreground",children:g}),a.count>0&&e.jsxs("div",{className:"flex items-center gap-2 text-sm",children:[e.jsx("div",{className:"flex",children:Array.from({length:5}).map((s,o)=>e.jsx(b,{className:`size-4 ${o<Math.round(a.avg)?"fill-warning text-warning":"text-muted-foreground/40"}`},o))}),e.jsx("span",{className:"font-semibold",children:a.avg.toFixed(1)}),e.jsxs("span",{className:"text-muted-foreground",children:["· ",a.count," avis"]})]}),e.jsxs("div",{className:"flex items-baseline gap-3 pt-2",children:[e.jsx("span",{className:"text-4xl font-bold",children:w(t.price)}),t.oldPrice&&e.jsx("span",{className:"text-lg text-muted-foreground line-through",children:w(t.oldPrice)})]}),e.jsxs("div",{className:"inline-flex items-center gap-1.5 text-sm font-semibold",style:{color:t.stock==="in"?"#10b981":t.stock==="low"?"#f59e0b":"#ef4444"},children:[t.stock==="in"?e.jsx(te,{className:"size-4"}):e.jsx(re,{className:"size-4"}),t.stock==="in"?r("pd.inStockReady"):t.stock==="low"?r("pd.lowStockOnly"):r("pd.outNow")]}),e.jsxs("div",{className:"flex flex-wrap gap-3 pt-2",children:[e.jsxs("button",{onClick:_,className:"inline-flex items-center gap-2 h-12 px-6 rounded-md bg-primary text-primary-foreground font-semibold hover:bg-primary/90 transition-colors shadow-[var(--shadow-glow)] flex-1 sm:flex-none justify-center",children:[e.jsx(ae,{className:"size-4"})," ",r("pd.addCart")]}),e.jsxs("button",{onClick:H,className:`inline-flex items-center gap-2 h-12 px-6 rounded-md border transition-colors ${p?"border-destructive/60 text-destructive":"border-border hover:border-primary/60"}`,children:[e.jsx(oe,{className:`size-4 ${p?"fill-current":""}`})," ",p?"Dans les favoris":r("pc.fav")]})]}),e.jsxs("div",{className:"grid sm:grid-cols-2 gap-3 pt-4",children:[e.jsxs("div",{className:"flex items-center gap-3 p-4 rounded-lg border border-border bg-card",children:[e.jsx(ie,{className:"size-5 text-primary shrink-0"}),e.jsxs("div",{className:"text-xs",children:[e.jsx("div",{className:"font-semibold text-sm",children:r("pd.warranty")}),e.jsx("div",{className:"text-muted-foreground",children:r("pd.warranty.sub")})]})]}),e.jsxs("div",{className:"flex items-center gap-3 p-4 rounded-lg border border-border bg-card",children:[e.jsx(de,{className:"size-5 text-primary shrink-0"}),e.jsxs("div",{className:"text-xs",children:[e.jsx("div",{className:"font-semibold text-sm",children:r("pd.delivery")}),e.jsx("div",{className:"text-muted-foreground",children:r("pd.delivery.sub")})]})]})]})]})]}),e.jsxs("section",{className:"mt-14",children:[e.jsx("div",{className:"flex gap-1 border-b border-border",children:K.map(s=>e.jsx("button",{onClick:()=>u(s.id),className:`h-11 px-5 text-sm font-medium border-b-2 -mb-px transition-colors ${l===s.id?"border-primary text-foreground":"border-transparent text-muted-foreground hover:text-foreground"}`,children:r(s.key)},s.id))}),e.jsxs("div",{className:"py-8",children:[l==="specs"&&e.jsx("dl",{className:"grid sm:grid-cols-2 gap-px bg-border rounded-xl overflow-hidden border border-border",children:Object.entries(t.specs).filter(([s])=>ue[s]).map(([s,o])=>e.jsxs("div",{className:"bg-card flex justify-between gap-4 px-5 py-3.5",children:[e.jsx("dt",{className:"text-sm text-muted-foreground",children:r(ue[s])}),e.jsx("dd",{className:"text-sm font-medium text-foreground text-end",children:j(o)})]},s))}),l==="desc"&&e.jsxs("p",{className:"text-muted-foreground leading-relaxed max-w-3xl",children:[i," — ",r("pd.desc")]}),l==="rev"&&e.jsxs("div",{className:"max-w-3xl space-y-6",children:[a.count>0&&e.jsxs("div",{className:"rounded-xl border border-border bg-card p-5 grid gap-5 sm:grid-cols-[auto_1fr] items-center",children:[e.jsxs("div",{className:"text-center",children:[e.jsx("div",{className:"text-4xl font-bold",children:a.avg.toFixed(1)}),e.jsx("div",{className:"flex justify-center mt-1",children:Array.from({length:5}).map((s,o)=>e.jsx(b,{className:`size-4 ${o<Math.round(a.avg)?"fill-warning text-warning":"text-muted-foreground/40"}`},o))}),e.jsxs("div",{className:"mt-1 text-xs text-muted-foreground",children:[a.count," avis"]})]}),e.jsx("div",{className:"space-y-1.5",children:[5,4,3,2,1].map(s=>{const o=B[s-1],n=a.count?o/a.count*100:0;return e.jsxs("div",{className:"flex items-center gap-2 text-xs",children:[e.jsxs("span",{className:"w-4 text-muted-foreground",children:[s,"★"]}),e.jsx("div",{className:"flex-1 h-1.5 rounded bg-muted overflow-hidden",children:e.jsx("div",{className:"h-full bg-warning",style:{width:`${n}%`}})}),e.jsx("span",{className:"w-6 text-right text-muted-foreground",children:o})]},s)})})]}),a.approved.length===0&&e.jsx("p",{className:"text-muted-foreground",children:r("pd.noRev")}),e.jsx("ul",{className:"space-y-3",children:a.approved.map(s=>e.jsxs("li",{className:"rounded-lg border border-border bg-card p-4",children:[e.jsxs("div",{className:"flex items-center justify-between gap-3 mb-1",children:[e.jsx("span",{className:"font-semibold text-sm",children:s.customerName}),e.jsx("div",{className:"flex",children:Array.from({length:5}).map((o,n)=>e.jsx(b,{className:`size-3.5 ${n<s.rating?"fill-warning text-warning":"text-muted-foreground/40"}`},n))})]}),s.title&&e.jsx("p",{className:"text-sm font-medium",children:s.title}),e.jsx("p",{className:"text-sm text-muted-foreground",children:s.body}),e.jsx("p",{className:"mt-1 text-[11px] text-muted-foreground",children:new Date(s.createdAt).toLocaleDateString("fr-FR")})]},s.id))}),m?e.jsxs("form",{onSubmit:q,className:"rounded-xl border border-border bg-card p-5 space-y-3",children:[e.jsx("h3",{className:"font-semibold text-sm",children:"Ajouter votre avis"}),V&&e.jsx("p",{className:"text-xs text-primary",children:"Votre précédent avis est en attente de modération."}),e.jsx("div",{className:"flex items-center gap-1",children:[1,2,3,4,5].map(s=>e.jsx("button",{type:"button",onClick:()=>P(s),"aria-label":`${s} étoiles`,children:e.jsx(b,{className:`size-6 transition ${s<=C?"fill-warning text-warning":"text-muted-foreground/40"}`})},s))}),e.jsx("input",{value:S,onChange:s=>$(s.target.value),maxLength:80,placeholder:"Titre de votre avis (optionnel)",className:"w-full h-10 rounded-md bg-background border border-border focus:border-primary outline-none px-3 text-sm"}),e.jsx("textarea",{value:y,onChange:s=>A(s.target.value),maxLength:500,rows:3,placeholder:"Partagez votre avis sur ce produit...",className:"w-full rounded-md bg-background border border-border focus:border-primary outline-none p-3 text-sm"}),e.jsx("button",{type:"submit",className:"h-10 px-5 rounded-md bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90",children:"Envoyer l'avis"})]}):e.jsx(v,{to:"/auth",className:"inline-flex h-10 px-5 items-center rounded-md bg-primary text-primary-foreground text-sm font-semibold",children:"Connectez-vous pour ajouter un avis"})]}),l==="ship"&&e.jsx("p",{className:"text-muted-foreground leading-relaxed max-w-3xl",children:r("pd.shipText")})]})]}),e.jsxs("section",{className:"mt-10",children:[e.jsx("h2",{className:"text-2xl font-bold mb-6",children:r("pd.related")}),e.jsx("div",{className:"grid gap-5 sm:grid-cols-2 lg:grid-cols-4",children:F.map(s=>e.jsx(le,{p:s},s.id))})]})]})}export{we as default};
