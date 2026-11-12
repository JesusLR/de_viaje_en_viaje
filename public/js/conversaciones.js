(() => {
    const root = document.getElementById("conversaciones");
    if (!root) return;
    const $ = (id) => document.getElementById(id),
        base = root.dataset.base;
    let selected = null,
        page = 1,
        offset = 0,
        busy = false,
        loading = false;
    const node = (tag, text, cls) => {
        const e = document.createElement(tag);
        e.textContent = text ?? "";
        if (cls) e.className = cls;
        return e;
    };
    const error = (msg) => {
        $("chatError").textContent = msg || "";
        $("chatError").classList.toggle("d-none", !msg);
    };
    function uuid() {
        if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID();
        const b=globalThis.crypto.getRandomValues(new Uint8Array(16));b[6]=(b[6]&15)|64;b[8]=(b[8]&63)|128;
        const h=Array.from(b,x=>x.toString(16).padStart(2,'0')).join('');return [h.slice(0,8),h.slice(8,12),h.slice(12,16),h.slice(16,20),h.slice(20)].join('-');
    }
    async function api(path, data) {
        const r = await fetch(base + path, {
            method: data ? "POST" : "GET",
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                ...(data instanceof FormData
                    ? {}
                    : { "Content-Type": "application/json" }),
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
            },
            ...(data
                ? {
                      body:
                          data instanceof FormData
                              ? data
                              : JSON.stringify(data),
                  }
                : {}),
        });
        const d = await r.json().catch(() => ({}));
        if (!r.ok)
            throw Object.assign(
                new Error(
                    d.message ||
                        d.mensaje ||
                        Object.values(d.errors || {})[0]?.[0] ||
                        "No fue posible completar la operación.",
                ),
                { status: r.status },
            );
        return d;
    }
    let historyPending=null;
    async function history() {
        if (!selected) return;
        const requestOffset=offset;
        const requestKey=selected.id+':'+offset;
        if(historyPending===requestKey)return;
        historyPending=requestKey;
        const id = selected.id;
        try {
            const d = await api("/" + id + "/mensajes?offset=" + offset);
            if (selected?.id !== id || offset !== requestOffset) return;
            $("chatAnteriores").disabled = offset + 100 >= d.total;
            $("chatRecientes").disabled = offset === 0;
            $("chatCliente").replaceChildren();
            if (d.cliente) {
                $("chatCliente").append(node("span", d.cliente.nombre));
                for (const k of ["ventas", "apartados", "cotizaciones"])
                    if (d.cliente[k]) {
                        const a = node(
                            "a",
                            k,
                            "btn btn-sm btn-outline-primary",
                        );
                        a.href = d.cliente[k];
                        a.target = "_blank";
                        a.rel = "noopener";
                        $("chatCliente").append(a);
                    }
            }
            if(d.warning) error(d.warning);
            const panel = $("chatMensajes"),
                bottom =
                    panel.scrollHeight - panel.scrollTop - panel.clientHeight <
                    80;
            panel.replaceChildren();
            if(!d.messages?.length) panel.append(node('p',d.warning || 'No hay mensajes disponibles en OpenWA ni en el historial consultado de WhatsApp.','chat-history-empty'));
            let lastDay='';
            [...(d.messages || [])].reverse().forEach((m) => {
                const date=m.timestamp?new Date(m.timestamp*1000):null;
                const day=date?date.toLocaleDateString('es-MX'):'';
                if(day && day!==lastDay){panel.append(node('div',day,'chat-day'));lastDay=day;}
                const box = node(
                    "div",
                    null,
                    "rounded p-2 mb-2 " +
                        (m.direction === "outgoing"
                            ? "bg-primary-subtle ms-4"
                            : "bg-light me-4"),
                );
                box.append(
                    node(
                        "small",
                        m.direction === "outgoing" ? "Agencia" : "Cliente",
                        "d-block text-muted",
                    ),
                    node("div", m.body || (m.type === "document" || m.attachment ? "" : "[" + m.type + "]")),
                );
                if (m.type === 'image' && m.image_url) {
                    const image=document.createElement('img');image.className='chat-message-image';image.alt='Imagen de la conversación';image.loading='lazy';image.src=m.image_url;
                    image.addEventListener('error',()=>{image.replaceWith(node('span','Imagen no disponible en OpenWA','chat-image-unavailable'))});
                    const link=node('a');link.href=m.image_url;link.setAttribute('aria-label','Ampliar imagen');link.addEventListener('click',event=>{event.preventDefault();$('chatImagenAmpliada').src=m.image_url;bootstrap.Modal.getOrCreateInstance($('chatImagenModal')).show();});link.append(image);box.prepend(link);
                }
                if (m.type !== 'image' && (m.type === "document" || m.attachment)) {
                    const card = node("div", null, "chat-attachment");
                    const filename = m.attachment?.filename || "Documento adjunto";
                    const isPdf = /\.pdf$/i.test(filename) || m.attachment?.mimetype === "application/pdf";
                    card.append(node("span", isPdf ? "PDF" : "ARCHIVO", "chat-attachment-icon"), node("strong", filename));
                    card.append(node("small", m.direction === "outgoing" ? "Documento enviado" : "Documento recibido", "d-block text-muted"));
                    box.prepend(card);
                }
                if(date) box.append(node('small', date.toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit'}) + (m.direction==='outgoing'?' · '+({read:'Leído',delivered:'Entregado',sent:'Enviado',pending:'Pendiente',failed:'Fallido'}[m.status]||'Enviado'):''), 'chat-message-time'));
                panel.append(box);
            });
            if (bottom) panel.scrollTop = panel.scrollHeight;
            if(!document.hidden && selected?.id===id && Number(selected.unread_count)>0){
                api('/'+id+'/leer',{}).then(()=>{if(selected?.id===id){selected.unread_count=0;$('chatLista').querySelector('[data-chat-id="'+id+'"] .chat-unread-dot')?.remove();}}).catch(()=>{});
            }
            $("chatEventos").replaceChildren(
                ...d.eventos.map((e) =>
                    node(
                        "p",
                        (e.name || "Sistema") +
                            " · " +
                            e.created_at +
                            " · " +
                            e.texto,
                        "small text-break",
                    ),
                ),
            );
        } catch (e) {
            if (selected?.id === id) {
                if (e.status === 403) {
                    selected = null;
                    $("chatPanel").classList.add("d-none");
                    $("chatMensajes").replaceChildren();
                    $("chatEventos").replaceChildren();
                }
                error(e.message);
            }
        } finally {
            if(historyPending===requestKey)historyPending=null;
        }
    }

    function reflejarResponsable() {
        const disponible = selected?.estado === "en_atencion" && Boolean(selected?.assigned_to);
        for (const formId of ["chatRespuesta", "chatDocumento"]) {
            $(formId)?.querySelectorAll("button,textarea,input").forEach(el=>el.disabled=!disponible || busy);
        }
        const aviso=$("chatEnvioAviso");
        if(aviso){aviso.classList.toggle("d-none",disponible || !selected);aviso.textContent=selected?.assigned_to ? "La atención está cerrada. Reábrela para enviar mensajes o documentos." : "Asigna un responsable en Responsable y seguimiento para habilitar el envío de mensajes y documentos.";}
        const selector = $("chatUsuario");
        if (selector) {
            selector.value = selected?.assigned_to == null ? "" : String(selected.assigned_to);
            if (selector.selectedIndex < 0) selector.value = "";
            if (window.jQuery) window.jQuery(selector).trigger("change.select2");
        }
        $("chatResponsable").textContent = "Responsable: " + (selected?.responsable?.name || "Sin asignar");
    }
    let abrirDesdeNotificacion = new URLSearchParams(location.search).get('conversacion');
    async function load() {
        if (loading || busy) return;
        loading = true;
        try {
            const d = await api(
                "/datos?" +
                    new URLSearchParams({
                        page,
                        estado: $("chatFiltro").value,
                        buscar: $("chatBuscar").value,
                    }),
            );
            root.classList.toggle('chat-offline',d.conectado===false);
            error(d.aviso);
            if(d.conectado===false){
                if($('chatImagenModal'))bootstrap.Modal.getInstance($('chatImagenModal'))?.hide();
                selected=null;root.classList.remove('chat-selected');$('chatPanel').classList.add('d-none');$('chatLista').replaceChildren();$('chatMensajes').replaceChildren();$('chatEventos').replaceChildren();
                $('chatAnterior').disabled=true;$('chatSiguiente').disabled=true;
                if($('chatUsuario'))$('chatUsuario').replaceChildren(new Option('Sin asignar',''));
                reflejarResponsable();return;
            }
            $("chatLista").replaceChildren(
                ...d.conversaciones.data.map((c) => {
                    const b = node(
                        "button",
                        c.nombre + " · " + c.estado,
                        "list-group-item list-group-item-action text-break",
                    );
                    b.type = "button";b.dataset.chatId=c.id;
                    b.classList.toggle('chat-active', selected?.id === c.id);
                    const initials=(c.nombre || '?').trim().split(/\s+/).slice(0,2).map(w=>w[0]).join('').toUpperCase();
                    b.replaceChildren(node('span',initials,'chat-avatar'));
                    const info=node('span',null,'chat-list-info');
                    info.append(node('strong',c.nombre),node('small',({sin_asignar:'Sin asignar',en_atencion:'En atención',atendida:'Atendida'})[c.estado] || c.estado));
                    b.append(info);
                    if(Number(c.unread_count)>0){const dot=node('span',null,'chat-unread-dot');dot.setAttribute('aria-label','Mensajes sin leer');dot.title='Mensajes sin leer';b.append(dot);}
                    if(c.last_timestamp) b.append(node('small',new Date(c.last_timestamp*1000).toLocaleDateString('es-MX',{day:'2-digit',month:'2-digit'}),'chat-list-time'));
                    b.onclick = () => {
                        if(selected?.id!==c.id){clearAttachment();if($('chatImagenModal'))bootstrap.Modal.getInstance($('chatImagenModal'))?.hide();}
                        selected = c; root.classList.add("chat-selected");
                        offset = 0;
                        $('chatLista').querySelectorAll('button').forEach(el=>el.classList.toggle('chat-active',el===b));
                        $('chatMensajes').replaceChildren(node('p','Cargando conversación…','chat-history-empty'));
                        $("chatPanel").classList.remove("d-none");
                        $("chatNombre").textContent = c.nombre;
                        $("chatResponsable").textContent =
                            "Responsable: " +
                            (c.responsable?.name || "Sin asignar");
                        reflejarResponsable();
                        history();
                    };
                    return b;
                }),
            );
            if (abrirDesdeNotificacion) {
                const index=d.conversaciones.data.findIndex(c=>String(c.id)===abrirDesdeNotificacion);
                if(index>=0){$("chatLista").children[index]?.click();abrirDesdeNotificacion=null;}
                else if(page<d.conversaciones.last_page){page++; loading=false;return load();}
                else abrirDesdeNotificacion=null;
            }
            $("chatAnterior").disabled = page <= 1;
            $("chatSiguiente").disabled = page >= d.conversaciones.last_page;
            if ($("chatUsuario")) {
                if (selected) selected = d.conversaciones.data.find(c => c.id === selected.id) || selected;
                $("chatUsuario").replaceChildren(
                    new Option("Sin asignar", ""),
                    ...d.usuarios.map((u) => new Option(u.nombre, u.id)),
                );
                reflejarResponsable();
            }
            if (selected) {
                selected = d.conversaciones.data.find(c=>c.id===selected.id) || selected;
                reflejarResponsable();
            }
            if (
                selected &&
                !d.conversaciones.data.some((c) => c.id === selected.id)
            ) {
                selected = null;
                root.classList.remove('chat-selected');
                $("chatPanel").classList.add("d-none");
                $("chatMensajes").replaceChildren();
                $("chatEventos").replaceChildren();
            }
        } catch (e) {
            error(e.message);
        } finally {
            loading = false;
        }
    }
    async function action(path, data) {
        if (!selected || busy) return false;
        busy = true;
        const id = selected.id;
        let failure = "";
        const loadingAlert = ["responder", "documento", "asignar"].includes(path) && Boolean(window.Swal);
        if (loadingAlert) {
            Swal.fire({
                title: "Cargando…",
                text: path === "asignar" ? "Guardando asignación…" : "Enviando mensaje…",
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading(),
            });
        }
        root.querySelectorAll("button").forEach((b) => (b.disabled = true));
        try {
            await api("/" + id + "/" + path, data);
            return true;
        } catch (e) {
            failure = e.message;
            return false;
        } finally {
            busy = false;
            root.querySelectorAll("button").forEach(
                (b) => (b.disabled = false),
            );
            try {
                await load();
                await history();
                reflejarResponsable();
            } finally {
                if (loadingAlert) Swal.close();
            }
            if (failure) error(failure);
        }
    }
    $("chatVolver")?.addEventListener("click",()=>root.classList.remove("chat-selected"));
    function clearAttachment() {
        if(!$('chatPdf'))return;
        $('chatPdf').value='';$('chatAdjuntoVista').classList.add('d-none');$('chatAdjuntoNombre').textContent='';$('chatTexto').required=true;
    }
    $('chatClip')?.addEventListener('click',()=>{if(!busy)$('chatPdf').click()});
    $('chatQuitarAdjunto')?.addEventListener('click',()=>{if(!busy)clearAttachment()});
    $('chatPdf')?.addEventListener('change',()=>{
        const file=$('chatPdf').files[0];
        if(!file){clearAttachment();return;}
        if(file.size>15*1024*1024){clearAttachment();error('Selecciona un archivo de hasta 15 MB.');return;}
        $('chatAdjuntoNombre').textContent=file.name;$('chatAdjuntoVista').classList.remove('d-none');$('chatTexto').required=false;error('');
    });
    $("chatTexto")?.addEventListener("keydown", (event) => {
        if (event.key !== "Enter" || event.shiftKey || event.isComposing || event.keyCode === 229) return;
        event.preventDefault();
        if (event.repeat || busy || !selected || selected.estado !== "en_atencion" || !selected.assigned_to) return;
        if (!$("chatTexto").value.trim() && !$("chatPdf")?.files.length) return;
        $("chatRespuesta").requestSubmit();
    });
    $("chatRespuesta")?.addEventListener("submit", async (e) => {
        e.preventDefault();
        if(busy)return;
        const file=$('chatPdf')?.files[0];
        if(file){
            const data=new FormData();data.append('documento',file);data.append('solicitud',uuid());
            if(await action('documento',data))clearAttachment();
        }else if(await action('responder',{texto:$('chatTexto').value,solicitud:uuid()}))$('chatTexto').value='';
    });
    $("chatNota")?.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (await action("nota", { texto: $("chatNotaTexto").value }))
            $("chatNotaTexto").value = "";
    });
    const assignOnChange=async()=>{
        const selector=$('chatUsuario');
        if(!selected || busy){reflejarResponsable();return;}
        const usuario=selector.value||null;
        if(String(usuario||'')===String(selected.assigned_to||''))return;
        selector.disabled=true;
        if(window.jQuery)window.jQuery(selector).trigger('change.select2');
        try{await action('asignar',{usuario});}
        finally{selector.disabled=false;reflejarResponsable();}
    };
    if($('chatUsuario')){
        if(window.jQuery)window.jQuery('#chatUsuario').on('change.chatAssignment',assignOnChange);
        else $('chatUsuario').addEventListener('change',assignOnChange);
    }
    $('chatImagenModal')?.addEventListener('hidden.bs.modal',()=>{$('chatImagenAmpliada').removeAttribute('src')});
    $("chatCerrar")?.addEventListener("click", () =>
        action("atender", { estado: "atendida" }),
    );
    $("chatReabrir")?.addEventListener("click", () =>
        action("atender", { estado: "en_atencion" }),
    );
    let searchTimer;
    $('chatBuscar').addEventListener('input',()=>{clearTimeout(searchTimer);searchTimer=setTimeout(()=>{page=1;load()},350)});
    $("chatFiltro").onchange = () => {
        page = 1;
        selected = null;
        root.classList.remove('chat-selected');
        $("chatPanel").classList.add("d-none");
        load();
    };
    $("chatAnterior").onclick = () => {
        page--;
        load();
    };
    $("chatSiguiente").onclick = () => {
        page++;
        load();
    };
    $("chatAnteriores").onclick = () => {
        offset += 100;
        history();
    };
    $("chatRecientes").onclick = () => {
        offset = Math.max(0, offset - 100);
        history();
    };
    setInterval(() => {
        if (!document.hidden && !busy) {
            load();
            history();
        }
    }, 15000);
    let revision=null,checkingChanges=false;
    setInterval(async()=>{
        if(document.hidden || busy || checkingChanges)return;
        checkingChanges=true;
        try{const d=await api('/cambios');if(d.revision!==revision){revision=d.revision;await load();await history();}}
        catch{}finally{checkingChanges=false;}
    },5000);
    load();
})();
