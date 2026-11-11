import{r as v,j as e}from"./vendor-react-C5iM2OHa.js";import{I as ve}from"./ImageCatalogue-B9twUtZr.js";import{u as Ne,d as ye,L as se}from"./vendor-router-DMvM_U1d.js";import{o as Le,Q as Ce,ab as Pe,bh as ke,a as Se,b as we,a7 as Te,$ as F,a0 as M,a1 as R,a2 as O,N as C,t as N,n as te,O as ie}from"./index-katech-2026.js";import{f as qe}from"./AdminLayout-C2H7ug00.js";import{C as Ae}from"./ConfirmDialog-CCLKutN_.js";import{I as d}from"./input-BoygO7kp.js";import{L as c}from"./label-CLPqDPM2.js";import{S as z,a as V,b as B,c as K,d as S}from"./select-B9dJRzQX.js";import{S as ne}from"./switch-CbnzqoBq.js";import{T as Ie,a as Qe,b as W,c as G}from"./tabs-UfjZDuYZ.js";import{u as De}from"./brands-w2sQmW7L.js";import{u as Ee}from"./media-assets-BR1zo4gr.js";import{A as Fe}from"./arrow-left-DAHUcHIK.js";import{T as oe}from"./trash-2-M8jH65mP.js";import{o as me,b as ce,c as U,e as le,u as Me,l as Re,s as P}from"./types-Dg1kyhLS.js";function f(a){return a==null?{fr:"",en:"",ar:""}:typeof a=="string"?{fr:a,en:"",ar:""}:{fr:a.fr??"",en:a.en??"",ar:a.ar??""}}function Oe(a){if(!a.fr.trim())return;if(!a.en.trim()&&!a.ar.trim())return a.fr;const l={fr:a.fr};return a.en.trim()&&(l.en=a.en),a.ar.trim()&&(l.ar=a.ar),l}function de(){return e.jsx("span",{"aria-label":"traduction manquante",className:"ms-1 inline-block size-1.5 rounded-full bg-amber-400 align-middle"})}const ze=P().trim().max(2048).optional(),h=me({fr:P().trim(),en:P().optional(),ar:P().optional()}),Ve=me({id:P().trim().min(2,"ID trop court (min 2 caractères)").max(60,"ID trop long (max 60)").regex(/^[a-z0-9-]+$/,"Lettres minuscules, chiffres et tirets uniquement"),name:h.refine(a=>a.fr.length>=2,{path:["fr"],message:"Nom requis (min 2 caractères)"}),brand:P().trim().min(1,"Marque requise").max(60),category:le(["laptops","desktops","builds","phones","gear"],{message:"Catégorie invalide"}),specLine:h.refine(a=>a.fr.length>=3,{path:["fr"],message:"Ligne de specs requise (min 3 caractères)"}),price:U.number({message:"Prix requis"}).int("Nombre entier requis").min(1,"Le prix doit être supérieur à 0").max(1e8,"Prix trop élevé"),oldPrice:Me([Re(""),U.number().int().min(0).max(1e8)]).optional(),costPrice:Me([Re(""),U.number().int().min(0).max(1e8)]).optional(),stock:le(["in","low","out"]),stockQty:U.number().int().min(0).max(1e6).optional(),image:ze,images:P().array().optional(),bestSeller:ce().optional(),sale:ce().optional(),cpu:h.optional(),ram:h.optional(),storage:h.optional(),gpu:h.optional(),screen:h.optional(),os:h.optional()}).superRefine((a,l)=>{a.oldPrice!==""&&a.oldPrice!==void 0&&Number(a.oldPrice)>0&&Number(a.oldPrice)<=a.price&&l.addIssue({code:"custom",path:["oldPrice"],message:"L'ancien prix doit être supérieur au prix actuel"}),a.sale&&(a.oldPrice===""||a.oldPrice===void 0||Number(a.oldPrice)<=a.price)&&l.addIssue({code:"custom",path:["oldPrice"],message:"Renseigner un ancien prix > prix pour activer la promotion"})});function j(){return{fr:"",en:"",ar:""}}function Be(){return{id:"",name:j(),brand:"",category:"laptops",specLine:j(),price:0,oldPrice:"",costPrice:"",stock:"in",stockQty:50,image:"",images:[],bestSeller:!1,sale:!1,cpu:j(),ram:j(),storage:j(),gpu:j(),screen:j(),os:j()}}function ue(a){const _gi=(Array.isArray(a.images)&&a.images.length>0)?a.images:(a.image?[a.image]:[]);return{id:a.id,name:f(a.name),brand:a.brand,category:a.category,specLine:f(a.specLine),price:a.price,oldPrice:a.oldPrice??"",costPrice:a.costPrice??a.specs?.costPrice??"",stock:a.stock,stockQty:a.stockQty??0,image:_gi[0]??a.image??"",images:_gi,bestSeller:!!a.bestSeller,sale:!!a.sale,cpu:f(a.specs.cpu),ram:f(a.specs.ram),storage:f(a.specs.storage),gpu:f(a.specs.gpu),screen:f(a.specs.screen),os:f(a.specs.os)}}function _(a){if(a)return Oe({fr:a.fr,en:a.en??"",ar:a.ar??""})}function na({mode:a}){const[newImageUrl,setNewImageUrl]=v.useState(""),[uploading,setUploading]=v.useState(!1),fileInputRef=v.useRef(null);const{data:l}=Le(),{data:w}=Ce(),{data:y}=De(),{data:T}=Ee(),{can:p,guard:q,denyReason:L}=Pe(),{create:A,update:Q,remove:pe}=ke(),H=Se(),{t:s}=we(),Y=Ne(),{id:k}=ye(),{data:m}=Te(a==="edit"?k:void 0),[t,J]=v.useState(m?ue(m):Be()),[n,I]=v.useState({}),[ge,X]=v.useState(!1),[Z,ee]=v.useState(!1),[xe,ae]=v.useState(!1);v.useEffect(()=>{if(a==="edit"&&k){const r=l.find(u=>u.id===k);r&&J(ue(r))}},[k,a,l]);const fe=v.useMemo(()=>y.map(r=>r.name).sort(),[y]);function o(r,u){J(g=>({...g,[r]:u})),I(g=>({...g,[r]:void 0}))}const he=async r=>{if(r.preventDefault(),!q(a==="new"?"create":"update",s(a==="new"?"admin.catalogue.produit_form.creer_garde":"admin.catalogue.produit_form.modifier_garde")))return;const g=Ve.safeParse(t);if(!g.success){const x={};for(const b of g.error.issues){const be=b.path.join(".");x[be]=b.message,b.path.length>0&&(x[b.path[0]]??=b.message)}I(x),N.error(s("admin.catalogue.produit_form.toast_champs_invalides"));return}const i=g.data;if(a==="new"&&l.some(x=>x.id===i.id)){I({id:s("admin.catalogue.produit_form.erreur_id_existant")}),N.error(s("admin.catalogue.produit_form.toast_id_existant"));return}if(!w.some(x=>x.slug===i.category)){I({category:s("admin.catalogue.produit_form.erreur_categorie_inconnue")}),N.error(s("admin.catalogue.produit_form.toast_categorie_inconnue"));return}const E={id:i.id,name:_(i.name)??i.name.fr,brand:i.brand,category:i.category,use:m?.use??[],specLine:_(i.specLine)??i.specLine.fr,specs:{cpu:_(i.cpu),ram:_(i.ram),storage:_(i.storage),gpu:_(i.gpu),screen:_(i.screen),os:_(i.os)},price:i.price,oldPrice:i.oldPrice?Number(i.oldPrice):void 0,costPrice:i.costPrice?Number(i.costPrice):void 0,stock:i.stock,stockQty:typeof i.stockQty=="number"?i.stockQty:m?.stockQty,image:((Array.isArray(t.images)&&t.images.length>0)?t.images[0]:(i.image||"")),images:((Array.isArray(t.images)&&t.images.length>0)?t.images:(i.image?[i.image]:[])),specs:{...i.specs,cpu:_(i.cpu),ram:_(i.ram),storage:_(i.storage),gpu:_(i.gpu),screen:_(i.screen),os:_(i.os),gallery:((Array.isArray(t.images)&&t.images.length>0)?t.images:(i.image?[i.image]:[]))},bestSeller:i.bestSeller,sale:i.sale};ee(!0);try{a==="new"?await A(E):await Q(E.id,E),N.success(s(a==="new"?"admin.catalogue.produit_form.toast_cree":"admin.catalogue.produit_form.toast_mis_a_jour")),Y("/admin/products")}catch(x){const b=te(x);N.error(b.message)}finally{ee(!1)}},je=async()=>{if(!m||!q("delete",s("admin.catalogue.produit_form.supprimer_garde")))return;ae(!0);const r=H(m.name)??"";try{await pe(m.id),N.success(s("admin.catalogue.produit_form.supprimer_description",{nom:r})),Y("/admin/products")}catch(u){const g=te(u);N.error(g.message)}finally{ae(!1)}},re=a==="new"?"create":"update",_e=H(m?.name)??"",D={in:"admin.catalogue.commun.stock_in",low:"admin.catalogue.commun.stock_low",out:"admin.catalogue.commun.stock_out"};return e.jsxs(qe,{title:s(a==="new"?"admin.catalogue.produit_form.titre_nouveau":"admin.catalogue.produit_form.titre_modifier"),description:a==="new"?s("admin.catalogue.produit_form.description_nouveau"):s("admin.catalogue.produit_form.description_modifier",{id:k??""}),actions:e.jsxs("div",{className:"flex gap-2",children:[e.jsx(se,{to:"/admin/products",children:e.jsxs(C,{variant:"outline",children:[e.jsx(Fe,{className:"me-2 h-4 w-4"}),s("admin.catalogue.produit_form.retour")]})}),a==="edit"&&m&&(p("delete")?e.jsxs(C,{variant:"destructive",onClick:()=>X(!0),children:[e.jsx(oe,{className:"me-2 h-4 w-4"}),s("admin.catalogue.produit_form.supprimer")]}):e.jsx("span",{title:L("delete"),className:"inline-flex",children:e.jsxs(C,{variant:"destructive",disabled:!0,children:[e.jsx(oe,{className:"me-2 h-4 w-4"}),s("admin.catalogue.produit_form.supprimer")]})}))]}),children:[e.jsxs("form",{onSubmit:he,className:"space-y-6",children:[e.jsxs("div",{className:"grid gap-6 lg:grid-cols-[1fr_320px]",children:[e.jsxs(F,{children:[e.jsx(M,{children:e.jsx(R,{children:s("admin.catalogue.produit_form.section_informations")})}),e.jsxs(O,{className:"space-y-4",children:[e.jsxs("div",{className:"grid gap-4 sm:grid-cols-2",children:[e.jsxs("div",{children:[e.jsx(c,{children:s("admin.catalogue.produit_form.label_id")}),e.jsx(d,{value:t.id,disabled:a==="edit",onChange:r=>o("id",r.target.value),placeholder:"ex: rog-strix-g16"}),n.id&&e.jsx("p",{className:"mt-1 text-xs text-destructive",children:n.id})]}),e.jsxs("div",{children:[e.jsx(c,{children:s("admin.catalogue.produit_form.label_marque")}),e.jsx(d,{list:"brand-list",value:t.brand,onChange:r=>o("brand",r.target.value)}),e.jsx("datalist",{id:"brand-list",children:fe.map(r=>e.jsx("option",{value:r},r))}),n.brand&&e.jsx("p",{className:"mt-1 text-xs text-destructive",children:n.brand})]}),e.jsxs("div",{children:[e.jsx(c,{children:s("admin.catalogue.produit_form.label_categorie")}),e.jsxs(z,{value:t.category,onValueChange:r=>o("category",r),children:[e.jsx(V,{children:e.jsx(B,{})}),e.jsx(K,{children:w.map(r=>e.jsx(S,{value:r.slug,children:r.label},r.slug))})]})]})]}),e.jsxs(Ie,{defaultValue:"fr",children:[e.jsxs(Qe,{children:[e.jsx(W,{value:"fr",children:"FR"}),e.jsxs(W,{value:"en",children:["EN",!t.name?.en?.trim()&&e.jsx(de,{})]}),e.jsxs(W,{value:"ar",children:["AR",!t.name?.ar?.trim()&&e.jsx(de,{})]})]}),e.jsxs(G,{value:"fr",className:"space-y-4 pt-2",children:[e.jsxs("div",{children:[e.jsx(c,{children:s("admin.catalogue.produit_form.label_nom")}),e.jsx(d,{value:t.name.fr,onChange:r=>o("name",{...t.name,fr:r.target.value})}),n["name.fr"]&&e.jsx("p",{className:"mt-1 text-xs text-destructive",children:n["name.fr"]}),n.name&&!n["name.fr"]&&e.jsx("p",{className:"mt-1 text-xs text-destructive",children:n.name})]}),e.jsxs("div",{children:[e.jsx(c,{children:s("admin.catalogue.produit_form.label_specs")}),e.jsx(d,{value:t.specLine.fr,onChange:r=>o("specLine",{...t.specLine,fr:r.target.value}),placeholder:"Intel i7 · 16GB · 1TB SSD"}),n["specLine.fr"]&&e.jsx("p",{className:"mt-1 text-xs text-destructive",children:n["specLine.fr"]}),n.specLine&&!n["specLine.fr"]&&e.jsx("p",{className:"mt-1 text-xs text-destructive",children:n.specLine})]}),e.jsx($,{values:{cpu:t.cpu,ram:t.ram,storage:t.storage,gpu:t.gpu,screen:t.screen,os:t.os},lang:"fr",onChange:(r,u)=>o(r,u)})]}),e.jsxs(G,{value:"en",className:"space-y-4 pt-2",children:[e.jsx("p",{className:"text-xs text-muted-foreground",children:s("admin.catalogue.produit_form.traductions_facultatives")}),e.jsxs("div",{children:[e.jsx(c,{children:s("admin.catalogue.produit_form.label_nom_en")}),e.jsx(d,{dir:"ltr",value:t.name.en??"",onChange:r=>o("name",{...t.name,en:r.target.value})})]}),e.jsxs("div",{children:[e.jsx(c,{children:s("admin.catalogue.produit_form.label_specs_en")}),e.jsx(d,{dir:"ltr",value:t.specLine.en??"",onChange:r=>o("specLine",{...t.specLine,en:r.target.value})})]}),e.jsx($,{values:{cpu:t.cpu,ram:t.ram,storage:t.storage,gpu:t.gpu,screen:t.screen,os:t.os},lang:"en",onChange:(r,u)=>o(r,u)})]}),e.jsxs(G,{value:"ar",className:"space-y-4 pt-2",children:[e.jsx("p",{className:"text-xs text-muted-foreground",children:s("admin.catalogue.produit_form.traductions_facultatives")}),e.jsxs("div",{children:[e.jsx(c,{className:"block text-end",children:s("admin.catalogue.produit_form.label_nom_ar")}),e.jsx(d,{dir:"rtl",className:"text-end",value:t.name.ar??"",onChange:r=>o("name",{...t.name,ar:r.target.value})})]}),e.jsxs("div",{children:[e.jsx(c,{className:"block text-end",children:s("admin.catalogue.produit_form.label_specs_ar")}),e.jsx(d,{dir:"rtl",className:"text-end",value:t.specLine.ar??"",onChange:r=>o("specLine",{...t.specLine,ar:r.target.value})})]}),e.jsx($,{values:{cpu:t.cpu,ram:t.ram,storage:t.storage,gpu:t.gpu,screen:t.screen,os:t.os},lang:"ar",onChange:(r,u)=>o(r,u)})]})]})]})]}),e.jsxs("div",{className:"space-y-4",children:[e.jsxs(F,{children:[e.jsx(M,{children:e.jsx(R,{children:s("admin.catalogue.produit_form.section_prix_stock")})}),e.jsxs(O,{className:"space-y-3",children:[e.jsxs("div",{children:[e.jsx(c,{children:s("admin.catalogue.produit_form.label_prix")}),e.jsx(d,{type:"number",value:t.price,onChange:r=>o("price",Number(r.target.value))}),n.price&&e.jsx("p",{className:"mt-1 text-xs text-destructive",children:n.price})]}),e.jsxs("div",{children:[e.jsx(c,{children:s("admin.catalogue.produit_form.label_ancien_prix")}),e.jsx(d,{type:"number",value:t.oldPrice,onChange:r=>o("oldPrice",r.target.value===""?"":Number(r.target.value))}),n.oldPrice&&e.jsx("p",{className:"mt-1 text-xs text-destructive",children:n.oldPrice})]}),e.jsxs("div",{children:[e.jsx(c,{children:"Prix d'achat / Coût (DA)"}),e.jsx(d,{type:"number",placeholder:"Ex: 42000",value:t.costPrice,onChange:r=>o("costPrice",r.target.value===""?"":Number(r.target.value))}),n.costPrice&&e.jsx("p",{className:"mt-1 text-xs text-destructive",children:n.costPrice}),t.price&&t.costPrice&&Number(t.costPrice)>0?e.jsx("p",{className:"mt-1 text-xs text-emerald-400 font-semibold",children:`Marge bénéficiaire : +${(Number(t.price)-Number(t.costPrice)).toLocaleString()} DA (${((Number(t.price)-Number(t.costPrice))/Number(t.price)*100).toFixed(1)}%)`}):null]}),e.jsxs("div",{children:[e.jsx(c,{children:s("admin.catalogue.produit_form.label_quantite")}),e.jsx(d,{type:"number",min:0,value:t.stockQty??0,onChange:r=>o("stockQty",Number(r.target.value))}),e.jsx("p",{className:"mt-1 text-xs text-muted-foreground",children:s("admin.catalogue.produit_form.hint_quantite")})]}),e.jsxs("div",{children:[e.jsx(c,{children:s("admin.catalogue.produit_form.label_etat_stock")}),e.jsxs(z,{value:t.stock,onValueChange:r=>o("stock",r),children:[e.jsx(V,{children:e.jsx(B,{})}),e.jsxs(K,{children:[e.jsx(S,{value:"in",children:s(D.in)}),e.jsx(S,{value:"low",children:s(D.low)}),e.jsx(S,{value:"out",children:s(D.out)})]})]})]}),e.jsxs("div",{className:"flex items-center justify-between rounded-md border p-2",children:[e.jsx(c,{htmlFor:"bs",className:"mb-0",children:s("admin.catalogue.produit_form.label_bestseller")}),e.jsx(ne,{id:"bs",checked:!!t.bestSeller,onCheckedChange:r=>o("bestSeller",r)})]}),e.jsxs("div",{className:"flex items-center justify-between rounded-md border p-2",children:[e.jsx(c,{htmlFor:"sale",className:"mb-0",children:s("admin.catalogue.produit_form.label_promotion")}),e.jsx(ne,{id:"sale",checked:!!t.sale,onCheckedChange:r=>o("sale",r)})]})]})]}),e.jsxs(F,{
  children:[
    e.jsx(M,{
      children:e.jsxs("div",{
        className:"flex items-center justify-between",
        children:[
          e.jsx(R,{children:"Photos & Galerie du produit"}),
          e.jsxs("span",{
            className:"text-xs font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20",
            children:[(Array.isArray(t.images)?t.images.length:(t.image?1:0))," photo(s)"]
          })
        ]
      })
    }),
    e.jsxs(O,{
      className:"space-y-4",
      children:[
        // Hidden multi-file input
        e.jsx("input",{
          ref:fileInputRef,
          type:"file",
          multiple:!0,
          accept:"image/*",
          onChange:async ev=>{
            const files=ev.target.files;
            if(!files||files.length===0)return;
            setUploading(!0);
            let count=0;
            const added=[];
            try{
              for(let idx=0;idx<files.length;idx++){
                const f=files[idx];
                const fd=new FormData();
                fd.append("file",f);
                const res=await fetch("/api/v1/media",{method:"POST",body:fd});
                if(res.ok){
                  const json=await res.json();
                  const src=json.data?.src||json.data?.url||(json.items&&json.items[0]?.src);
                  if(src){added.push(src);count++}
                }
              }
              if(added.length>0){
                const current=Array.isArray(t.images)?t.images:(t.image?[t.image]:[]);
                const updated=[...current,...added];
                o("images",updated);
                if(!t.image||t.image==="")o("image",updated[0]);
                N.success(`${count} photo(s) ajoutée(s) avec succès !`);
              }else{
                N.error("Échec du téléversement.");
              }
            }catch(err){
              N.error("Erreur de téléversement : "+err.message);
            }finally{
              setUploading(!1);
              if(fileInputRef.current)fileInputRef.current.value="";
            }
          },
          className:"hidden"
        }),

        // Upload Button / Dropzone
        e.jsxs("button",{
          type:"button",
          onClick:()=>fileInputRef.current?.click(),
          disabled:uploading,
          className:"w-full flex flex-col items-center justify-center gap-2 p-5 rounded-xl border-2 border-dashed border-primary/40 hover:border-primary bg-primary/5 hover:bg-primary/10 transition cursor-pointer text-center",
          children:[
            e.jsx("svg",{
              xmlns:"http://www.w3.org/2000/svg",
              width:"24",
              height:"24",
              viewBox:"0 0 24 24",
              fill:"none",
              stroke:"currentColor",
              strokeWidth:"2",
              strokeLinecap:"round",
              strokeLinejoin:"round",
              className:`size-7 text-primary ${uploading?"animate-bounce":""}`,
              children:[
                e.jsx("path",{d:"M12 3v12"}),
                e.jsx("path",{d:"m17 8-5-5-5 5"}),
                e.jsx("path",{d:"M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"})
              ]
            }),
            e.jsx("span",{
              className:"font-semibold text-xs sm:text-sm text-foreground",
              children:uploading?"Téléversement en cours...":"Cliquez pour choisir des photos (Plusieurs fichiers acceptés)"
            }),
            e.jsx("span",{
              className:"text-[11px] text-muted-foreground",
              children:"Sélectionnez une ou plusieurs images (JPG, PNG, WEBP) depuis votre ordinateur"
            })
          ]
        }),

        // Add by URL and Media library
        e.jsxs("div",{
          className:"space-y-2 pt-2 border-t border-border",
          children:[
            e.jsx(c,{children:"Ajouter une photo par lien URL ou médiathèque"}),
            e.jsxs("div",{
              className:"flex gap-2",
              children:[
                e.jsx(d,{
                  value:newImageUrl,
                  onChange:ev=>setNewImageUrl(ev.target.value),
                  onKeyDown:ev=>{
                    if(ev.key==="Enter"){
                      ev.preventDefault();
                      if(newImageUrl.trim()){
                        const cur=Array.isArray(t.images)?t.images:(t.image?[t.image]:[]);
                        const upd=[...cur,newImageUrl.trim()];
                        o("images",upd);
                        if(!t.image||t.image==="")o("image",upd[0]);
                        setNewImageUrl("");
                      }
                    }
                  },
                  placeholder:"https://res.cloudinary.com/... ou /uploads/..."
                }),
                e.jsx(C,{
                  type:"button",
                  variant:"outline",
                  onClick:()=>{
                    if(newImageUrl.trim()){
                      const cur=Array.isArray(t.images)?t.images:(t.image?[t.image]:[]);
                      const upd=[...cur,newImageUrl.trim()];
                      o("images",upd);
                      if(!t.image||t.image==="")o("image",upd[0]);
                      setNewImageUrl("");
                    }
                  },
                  children:"Ajouter URL"
                })
              ]
            }),
            T.length>0&&e.jsx("div",{
              className:"pt-1",
              children:e.jsxs(z,{
                value:"",
                onValueChange:val=>{
                  if(val){
                    const cur=Array.isArray(t.images)?t.images:(t.image?[t.image]:[]);
                    const upd=[...cur,val];
                    o("images",upd);
                    if(!t.image||t.image==="")o("image",upd[0]);
                  }
                },
                children:[
                  e.jsx(V,{children:e.jsx(B,{placeholder:"Ou choisir depuis la médiathèque..."})}),
                  e.jsx(K,{children:T.map(r=>e.jsx(S,{value:r.src,children:r.name},r.id))})
                ]
              })
            })
          ]
        }),

        // Preview section
        e.jsxs("div",{
          className:"space-y-2.5 pt-2 border-t border-border",
          children:[
            e.jsxs("div",{
              className:"flex items-center justify-between",
              children:[
                e.jsx(c,{className:"font-semibold text-xs text-foreground",children:"Aperçu avant enregistrement"}),
                e.jsx("span",{className:"text-[11px] text-muted-foreground",children:"La 1ère est l'image principale"})
              ]
            }),
            ((Array.isArray(t.images)&&t.images.length>0)||t.image)?e.jsx("div",{
              className:"grid grid-cols-2 sm:grid-cols-3 gap-2.5 max-h-72 overflow-y-auto p-1",
              children:(Array.isArray(t.images)&&t.images.length>0?t.images:[t.image]).map((img,idx)=>
                e.jsxs("div",{
                  key:idx,
                  className:`group relative aspect-square rounded-xl border bg-card overflow-hidden shadow-sm transition hover:shadow-md ${idx===0?"border-primary ring-2 ring-primary/30":"border-border"}`,
                  children:[
                    e.jsx(ve,{src:img,alt:`Photo ${idx+1}`,className:"size-full object-cover"}),
                    idx===0?e.jsxs("span",{
                      className:"absolute top-1.5 left-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary text-primary-foreground shadow flex items-center gap-1 z-10",
                      children:["★"," Principale"]
                    }):e.jsxs("span",{
                      className:"absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-background/80 backdrop-blur-sm text-muted-foreground border border-border z-10",
                      children:["#",idx+1]
                    }),
                    e.jsx("button",{
                      type:"button",
                      onClick:()=>{
                        const cur=Array.isArray(t.images)?t.images:[t.image];
                        const upd=cur.filter((_,i)=>i!==idx);
                        o("images",upd);
                        o("image",upd[0]??"");
                      },
                      title:"Supprimer cette photo",
                      className:"absolute top-1.5 right-1.5 p-1 rounded-full bg-destructive text-destructive-foreground hover:bg-destructive/90 shadow transition z-10 opacity-90 hover:opacity-100",
                      children:e.jsx("svg",{xmlns:"http://www.w3.org/2000/svg",width:"12",height:"12",viewBox:"0 0 24 24",fill:"none",stroke:"currentColor",strokeWidth:"2.5",strokeLinecap:"round",strokeLinejoin:"round",className:"size-3",children:[e.jsx("path",{d:"M18 6 6 18"}),e.jsx("path",{d:"m6 6 12 12"})]})
                    }),
                    e.jsxs("div",{
                      className:"absolute bottom-0 inset-x-0 p-1 bg-gradient-to-t from-black/85 via-black/50 to-transparent flex items-center justify-between gap-1 z-10",
                      children:[
                        idx!==0?e.jsx("button",{
                          type:"button",
                          onClick:()=>{
                            const cur=Array.isArray(t.images)?t.images:[t.image];
                            const target=cur[idx];
                            const rem=cur.filter((_,i)=>i!==idx);
                            const upd=[target,...rem];
                            o("images",upd);
                            o("image",target);
                          },
                          className:"text-[10px] font-semibold bg-white/90 hover:bg-white text-black px-1.5 py-0.5 rounded shadow-sm transition truncate",
                          children:"Définir princ."
                        }):e.jsx("span",{className:"text-[10px] text-white/90 font-medium px-1",children:"Couverture"}),
                        e.jsxs("div",{
                          className:"flex items-center gap-0.5",
                          children:[
                            e.jsx("button",{
                              type:"button",
                              disabled:idx===0,
                              onClick:()=>{
                                const cur=[...(Array.isArray(t.images)?t.images:[t.image])];
                                const[rem]=cur.splice(idx,1);
                                cur.splice(idx-1,0,rem);
                                o("images",cur);
                                o("image",cur[0]);
                              },
                              className:"px-1 py-0.5 rounded bg-black/60 text-white hover:bg-black disabled:opacity-30 disabled:pointer-events-none transition text-[10px]",
                              children:"◀"
                            }),
                            e.jsx("button",{
                              type:"button",
                              disabled:idx===(Array.isArray(t.images)?t.images.length:1)-1,
                              onClick:()=>{
                                const cur=[...(Array.isArray(t.images)?t.images:[t.image])];
                                const[rem]=cur.splice(idx,1);
                                cur.splice(idx+1,0,rem);
                                o("images",cur);
                                o("image",cur[0]);
                              },
                              className:"px-1 py-0.5 rounded bg-black/60 text-white hover:bg-black disabled:opacity-30 disabled:pointer-events-none transition text-[10px]",
                              children:"▶"
                            })
                          ]
                        })
                      ]
                    })
                  ]
                })
              )
            }):e.jsx("div",{
              className:"p-5 rounded-xl border border-dashed border-border bg-muted/20 text-center text-xs text-muted-foreground",
              children:"Aucune photo ajoutée pour l'instant. Téléversez des fichiers ci-dessus ou collez une URL."
            })
          ]
        })
      ]
    })
  ]
})]})]}),e.jsxs("div",{className:"flex justify-end gap-2",children:[e.jsx(se,{to:"/admin/products",children:e.jsx(C,{variant:"outline",type:"button",children:s("admin.catalogue.produit_form.annuler")})}),p(re)?e.jsx(C,{type:"submit",disabled:Z,children:s(Z?"admin.catalogue.produit_form.enregistrement":a==="new"?"admin.catalogue.produit_form.creer":"admin.catalogue.produit_form.enregistrer")}):e.jsx("span",{title:L(re),className:"inline-flex",children:e.jsx(C,{type:"submit",disabled:!0,children:s(a==="new"?"admin.catalogue.produit_form.creer":"admin.catalogue.produit_form.enregistrer")})})]})]}),e.jsx(Ae,{open:ge,onOpenChange:X,title:s("admin.catalogue.produit_form.supprimer_titre"),description:m?s("admin.catalogue.produit_form.supprimer_description",{nom:_e}):null,confirmLabel:s(xe?"admin.catalogue.produit_form.suppression":"admin.catalogue.produit_form.supprimer"),destructive:!0,onConfirm:je})]})}function $({values:a,lang:l,onChange:w}){const y=l==="ar",T=y?"rtl":"ltr";function p(q,L){const A=f(a[L]);return e.jsxs("div",{children:[e.jsx(c,{className:ie(y&&"block text-end"),children:q}),e.jsx(d,{dir:T,className:ie(y&&"text-end"),value:A[l],onChange:Q=>w(L,{...A,[l]:Q.target.value})})]},L)}return e.jsxs("div",{className:"grid gap-3 sm:grid-cols-2",children:[p("CPU","cpu"),p("RAM","ram"),p("Stockage","storage"),p("GPU","gpu"),p("Écran","screen"),p("OS","os")]})}export{na as A};
