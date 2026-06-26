<script setup>
import { reactive, computed, onMounted, watch, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import BnkImageSlot from '@/components/BnkImageSlot.vue';
import BnkInfo from '@/components/BnkInfo.vue';

// Demo "props" (mirrors the design's configurable props)
const props = { showReferral: true, defaultPeriod: 'month' };

// Default photographs (real estate / construction). Users can replace any of
// these by clicking/dropping their own — see BnkImageSlot.
// Brand logo: logo_1 is the light wordmark (for dark backgrounds), logo_2 the
// dark wordmark (for light backgrounds / white document pages).
const LOGO_LIGHT = '/img/bankrol_logo_2.png';
const LOGO_DARK = '/img/bankrol_logo_1.png';

const HERO_PHOTO = 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1600&q=80&auto=format&fit=crop';
const SITE_PHOTOS = [
    'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=600&q=80&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1541888946425-d81bb19240f5?w=600&q=80&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1581094794329-c8112a89af12?w=600&q=80&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=600&q=80&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=600&q=80&auto=format&fit=crop',
    'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=600&q=80&auto=format&fit=crop',
];

const state = reactive({
    theme: 'light', authed: false, screen: 'dashboard', period: 'month',
    portFilter: 'All', clientFilter: 'All', reportTab: 'Sales',
    selClient: null, selProj: null,
    moreOpen: false, drawerOpen: false, drawerProj: null, docOpen: false, docType: null,
    hintDismissed: false,
});

function dismissHint() { set({ hintDismissed: true }); try { localStorage.setItem('bnk_hint', '1'); } catch (e) { /* ignore */ } }

function set(patch) { Object.assign(state, patch); }
function go(screen) { set({ screen, drawerOpen: false }); window.scrollTo(0, 0); }
function toggleTheme() { const theme = state.theme === 'dark' ? 'light' : 'dark'; set({ theme }); try { localStorage.setItem('bnk_theme', theme); } catch (e) { /* ignore */ } }
function signIn() { set({ authed: true }); try { localStorage.setItem('bnk_authed', '1'); } catch (e) { /* ignore */ } }
function signOut() { set({ authed: false, screen: 'dashboard', drawerOpen: false, docOpen: false, moreOpen: false }); try { localStorage.removeItem('bnk_authed'); } catch (e) { /* ignore */ } window.scrollTo(0, 0); }
function openDoc(type) { set({ docOpen: true, docType: type }); }
function closeDoc() { set({ docOpen: false }); }
function printDoc() { window.print(); }
function closeMore() { set({ moreOpen: false }); }
function openDrawer(p) { set({ drawerOpen: true, drawerProj: p }); }
function closeDrawer() { set({ drawerOpen: false }); }
function stop(e) { e.stopPropagation(); }
function openProj(proj) { set({ screen: 'build-detail', selProj: proj, drawerOpen: false }); window.scrollTo(0, 0); }
function genReceipt() { openDoc('Receipt'); }

function bar(w) { return `height:100%;width:${w}%;border-radius:5px;background:linear-gradient(90deg,var(--goldb),var(--gold));`; }

function statusBadge(status) {
    const map = { Lead: ['var(--steels)', 'var(--steel)'], Negotiation: ['var(--ambers)', 'var(--amber)'], Reserved: ['var(--golds)', 'var(--gold)'], Documentation: ['var(--steels)', 'var(--steel)'], Purchased: ['var(--greens)', 'var(--green)'] };
    const [bg, c] = map[status] || ['var(--golds)', 'var(--gold)'];
    return `display:inline-flex;align-items:center;font-size:11px;font-weight:700;letter-spacing:.02em;padding:4px 11px;border-radius:20px;background:${bg};color:${c};`;
}

// Count-up animation, replicated from the design
function runCountups() {
    const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    requestAnimationFrame(() => {
        document.querySelectorAll('[data-countup]').forEach((el) => {
            if (el.__done) return; el.__done = true;
            const value = parseFloat(el.getAttribute('data-value')); if (isNaN(value)) return;
            const prefix = el.getAttribute('data-prefix') || '', suffix = el.getAttribute('data-suffix') || '', dec = parseInt(el.getAttribute('data-decimals') || '0', 10);
            const fmt = (vv) => prefix + vv.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + suffix;
            if (reduce) { el.textContent = fmt(value); return; }
            const start = performance.now(), dur = 1100, ease = (t) => 1 - Math.pow(1 - t, 3);
            const tick = (now) => { const t = Math.min(1, (now - start) / dur); el.textContent = fmt(value * ease(t)); if (t < 1) requestAnimationFrame(tick); else el.textContent = fmt(value); };
            requestAnimationFrame(tick);
        });
    });
}

watch(() => state.period, () => { document.querySelectorAll('[data-countup]').forEach((el) => { el.__done = false; }); });
watch([() => state.screen, () => state.authed, () => state.period], () => { nextTick(runCountups); });

onMounted(() => {
    try {
        const t = localStorage.getItem('bnk_theme'); if (t) set({ theme: t });
        const a = localStorage.getItem('bnk_authed'); if (a === '1') set({ authed: true });
        if (localStorage.getItem('bnk_hint') === '1') set({ hintDismissed: true });
    } catch (e) { /* ignore */ }
    if (props.defaultPeriod && ['month', 'quarter', 'year'].includes(props.defaultPeriod)) set({ period: props.defaultPeriod });
    nextTick(runCountups);
});

const v = computed(() => {
    const s = state, theme = s.theme;
    const scr = s.screen;
    const showReferral = props.showReferral !== false;

    // ---------- NAV ----------
    const navDefs = [['dashboard', 'Dashboard', 'grid_view'], ['portfolio', 'Portfolio', 'apartment'], ['clients', 'Clients', 'groups'], ['build', 'Build', 'architecture'], ['finance', 'Finance', 'account_balance'], ['documents', 'Documents', 'article'], ['reports', 'Reports', 'insights']];
    const navItems = navDefs.map(([id, label, icon]) => {
        const active = scr === id || (id === 'clients' && scr === 'client-profile') || (id === 'build' && scr === 'build-detail');
        return { label, icon, onClick: () => go(id), style: `display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:11px;border:none;cursor:pointer;text-align:left;width:100%;transition:all .2s ease;background:${active ? 'var(--golds)' : 'transparent'};color:${active ? 'var(--gold)' : 'var(--t2)'};font-weight:${active ? '700' : '600'};` };
    });
    const mobBtn = (active) => `display:flex;flex-direction:column;align-items:center;gap:3px;padding:6px 8px;min-width:52px;background:transparent;border:none;cursor:pointer;color:${active ? 'var(--gold)' : 'var(--t3)'};`;
    const mobileDefs = [['dashboard', 'Home', 'grid_view'], ['portfolio', 'Portfolio', 'apartment'], ['clients', 'Clients', 'groups'], ['build', 'Build', 'architecture']];
    const mobileNav = mobileDefs.map(([id, label, icon]) => {
        const active = scr === id || (id === 'clients' && scr === 'client-profile') || (id === 'build' && scr === 'build-detail');
        return { label, icon, onClick: () => go(id), style: mobBtn(active) };
    });
    mobileNav.push({ label: 'More', icon: 'menu', onClick: () => set({ moreOpen: true }), style: mobBtn(['finance', 'documents', 'reports'].includes(scr)) });
    const moreRow = (active) => `display:flex;align-items:center;gap:14px;width:100%;text-align:left;padding:14px;border:none;border-radius:12px;cursor:pointer;margin-bottom:4px;background:${active ? 'var(--golds)' : 'transparent'};color:${active ? 'var(--gold)' : 'var(--text)'};`;
    const moreItems = [['finance', 'Finance', 'account_balance'], ['documents', 'Documents', 'article'], ['reports', 'Reports', 'insights']].map(([id, label, icon]) => ({ label, icon, onClick: () => { go(id); set({ moreOpen: false }); }, style: moreRow(scr === id) }));
    moreItems.push({ label: theme === 'dark' ? 'Light mode' : 'Dark mode', icon: theme === 'dark' ? 'light_mode' : 'dark_mode', onClick: () => { toggleTheme(); set({ moreOpen: false }); }, style: moreRow(false) });
    moreItems.push({ label: 'Sign out', icon: 'logout', onClick: () => { signOut(); }, style: moreRow(false) });

    const titleMap = { dashboard: ['Executive Dashboard', 'Develop → Build → Sell → Handover, in one view'], portfolio: ['Estate & Project Portfolio', 'Everything in progress across the business'], clients: ['Clients', 'Referral-driven private sales'], 'client-profile': ['Client Profile', 'Relationship & history'], build: ['Build', 'Live construction progress across active projects'], 'build-detail': ['Build Timeline', 'Live progress'], finance: ['Finance & Cash', 'Receivables, payables and payments'], documents: ['Document Center', 'Generate and manage paperwork'], reports: ['Reports', 'Performance across the business'] };
    const pt = titleMap[scr] || ['', ''];
    const periodLabels = { month: 'This Month', quarter: 'Quarter', year: 'Year' };
    const pillStyle = (active) => `font:600 12px 'Hanken Grotesk',sans-serif;padding:7px 15px;border-radius:9px;border:none;cursor:pointer;transition:all .25s ease;background:${active ? 'var(--navy)' : 'transparent'};color:${active ? '#E3CB97' : 'var(--t2)'};`;
    const periods = [['month', 'This Month'], ['quarter', 'Quarter'], ['year', 'Year']].map(([id, label]) => ({ label, onClick: () => set({ period: id }), style: pillStyle(s.period === id) }));

    // ---------- PROJECTS ----------
    const hColor = { ontrack: 'var(--green)', atrisk: 'var(--amber)', delayed: 'var(--alert)' };
    const hSoft = { ontrack: 'var(--greens)', atrisk: 'var(--ambers)', delayed: 'var(--alerts)' };
    const hLabel = { ontrack: 'On track', atrisk: 'At risk', delayed: 'Delayed' };
    const catColor = { Estates: ['var(--golds)', 'var(--gold)'], Construction: ['var(--steels)', 'var(--steel)'], Infrastructure: ['rgba(28,46,74,.10)', 'var(--navyt)'], Energy: ['var(--greens)', 'var(--green)'] };
    const tagStyleOf = (cat) => { const [bg, c] = catColor[cat] || ['var(--golds)', 'var(--gold)']; return `display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.02em;padding:3px 10px;border-radius:7px;background:${bg};color:${c};`; };
    const healthBadgeOf = (h) => `display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;padding:3px 11px;border-radius:20px;background:${hSoft[h]};color:${hColor[h]};`;

    const projectDefs = [
        { name: 'Bankrol Heights', location: 'Abuja', client: 'In-house development', cat: 'Estates', value: 3.2, pct: 45, health: 'ontrack', next: 'Block C roofing — Jul 2026', budget: 3.2, spent: 1.5, margin: '31%', engineer: 'Engr. Musa Ibrahim', timeline: 'Target Q2 2027', units: { sold: 9, reserved: 4, available: 11, total: 24, soldVal: 1.20, resVal: 0.53, availVal: 1.47 } },
        { name: 'Naval Base Infrastructure Upgrade', location: 'Lagos', client: 'Nigerian Navy', cat: 'Infrastructure', value: 4.8, pct: 62, health: 'ontrack', next: 'Phase 2 jetty works', budget: 4.8, spent: 3.0, margin: '18%', engineer: 'Engr. Peter Obi', timeline: 'Target Q4 2026' },
        { name: 'Katampe Extension Mixed-Use Block', location: 'Abuja', client: 'Private — Katampe Devs', cat: 'Construction', value: 5.4, pct: 18, health: 'ontrack', next: 'Substructure completion', budget: 5.4, spent: 1.0, margin: '22%', engineer: 'Engr. Funke Ade', timeline: 'Target Q3 2027' },
        { name: 'FCT Satellite Town Road Network', location: 'Abuja', client: 'FCTA', cat: 'Infrastructure', value: 1.9, pct: 78, health: 'atrisk', next: 'Asphalt — Phase 3', budget: 1.9, spent: 2.05, margin: '6%', engineer: 'Engr. Musa Ibrahim', timeline: 'Target Q3 2026' },
        { name: 'Keffi Solar Mini-Grid', location: 'Nasarawa', client: 'REA / Nasarawa State', cat: 'Energy', value: 2.1, pct: 30, health: 'delayed', next: 'Panel delivery overdue', budget: 2.1, spent: 0.65, margin: '19%', engineer: 'Engr. Peter Obi', timeline: 'Target Q1 2027' },
        { name: 'Gwarinpa Estate Renovation', location: 'Abuja', client: 'Private', cat: 'Construction', value: 0.88, pct: 90, health: 'ontrack', next: 'Final finishes & snagging', budget: 0.88, spent: 0.80, margin: '24%', engineer: 'Engr. Funke Ade', timeline: 'Target Q3 2026' },
    ];
    const fmtB = (val) => '₦' + (Math.round(val * 100) / 100) + 'B';
    const stageNames = ['Foundation', 'Structure', 'Roof', 'Electrical', 'Plumbing', 'Painting', 'Interior', 'Inspection', 'Completion'];
    const projects = projectDefs.map((p) => ({
        ...p,
        value: fmtB(p.value), pct: p.pct + '%',
        dot: `width:9px;height:9px;border-radius:50%;background:${hColor[p.health]};flex:none;`,
        tag: p.cat, tagStyle: tagStyleOf(p.cat),
        healthLabel: hLabel[p.health], healthBadge: healthBadgeOf(p.health),
        bar: bar(p.pct),
        onOpen: () => openDrawer(p),
    }));
    const filterTabs = ['All', 'Estates', 'Construction', 'Infrastructure', 'Energy'];
    const portFilters = filterTabs.map((f) => ({ label: f, onClick: () => set({ portFilter: f }), style: `font:600 11.5px 'Hanken Grotesk',sans-serif;padding:6px 13px;border-radius:8px;cursor:pointer;transition:all .2s ease;border:1px solid ${s.portFilter === f ? 'var(--gold)' : 'var(--border)'};background:${s.portFilter === f ? 'var(--golds)' : 'transparent'};color:${s.portFilter === f ? 'var(--gold)' : 'var(--t2)'};` }));
    const portFiltersWide = filterTabs.map((f) => ({ label: f, onClick: () => set({ portFilter: f }), style: `font:600 12px 'Hanken Grotesk',sans-serif;padding:8px 16px;border-radius:30px;cursor:pointer;transition:all .2s ease;border:1px solid ${s.portFilter === f ? 'var(--gold)' : 'var(--border)'};background:${s.portFilter === f ? 'var(--golds)' : 'var(--card)'};color:${s.portFilter === f ? 'var(--gold)' : 'var(--t2)'};` }));
    const matches = (p) => s.portFilter === 'All' || p.cat === s.portFilter;
    const filteredProjects = projects.filter(matches);

    // ---------- DRAWER ----------
    const drP = s.drawerProj || projectDefs[0];
    const milestoneStages = ['Mobilization', 'Substructure', 'Superstructure', 'Roofing', 'Services', 'Finishes', 'Handover'];
    const mDone = Math.round(drP.pct / 100 * milestoneStages.length);
    const dr = {
        name: drP.name, location: drP.location, client: drP.client, tag: drP.cat,
        tagStyleLight: `display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.04em;padding:3px 11px;border-radius:7px;background:rgba(227,203,151,.16);color:#E3CB97;`,
        pct: drP.pct + '%', bar: `height:100%;width:${drP.pct}%;border-radius:5px;background:linear-gradient(90deg,#E3CB97,#C9A86A);`,
        healthBadge: healthBadgeOf(drP.health), healthLabel: hLabel[drP.health],
        stats: [['Contract Value', fmtB(drP.value)], ['Cost to Date', fmtB(drP.spent)], ['Margin', drP.margin], ['Client', drP.client]].map(([k, val]) => ({ k, v: val })),
        isEstate: drP.cat === 'Estates',
        units: drP.units ? [
            { label: 'Sold', units: drP.units.sold, value: fmtB(drP.units.soldVal), dot: 'width:9px;height:9px;border-radius:3px;background:var(--navy);', seg: `width:${drP.units.sold / drP.units.total * 100}%;background:var(--navy);` },
            { label: 'Reserved', units: drP.units.reserved, value: fmtB(drP.units.resVal), dot: 'width:9px;height:9px;border-radius:3px;background:var(--gold);', seg: `width:${drP.units.reserved / drP.units.total * 100}%;background:var(--gold);` },
            { label: 'Available', units: drP.units.available, value: fmtB(drP.units.availVal), dot: 'width:9px;height:9px;border-radius:3px;background:var(--steel);', seg: `width:${drP.units.available / drP.units.total * 100}%;background:var(--steel);` },
        ] : [],
        milestones: milestoneStages.map((m, i) => {
            const st = i < mDone ? 'done' : (i === mDone ? 'active' : 'todo');
            const dot = st === 'done' ? 'width:13px;height:13px;border-radius:50%;background:var(--gold);box-shadow:0 0 0 3px var(--golds);flex:none;' : st === 'active' ? 'width:13px;height:13px;border-radius:50%;background:var(--navy);box-shadow:0 0 0 3px var(--golds);flex:none;' : 'width:13px;height:13px;border-radius:50%;background:var(--card2);border:2px solid var(--border);flex:none;';
            const chip = st === 'done' ? 'font-size:10px;font-weight:700;padding:2px 9px;border-radius:20px;background:var(--greens);color:var(--green);' : st === 'active' ? 'font-size:10px;font-weight:700;padding:2px 9px;border-radius:20px;background:var(--navy);color:#E3CB97;' : 'font-size:10px;font-weight:700;padding:2px 9px;border-radius:20px;background:var(--card2);color:var(--t3);';
            const status = st === 'done' ? 'Complete' : (st === 'active' ? 'In progress' : 'Upcoming');
            return { name: m, dot, chip, status };
        }),
        onTimeline: () => openProj(drP),
    };

    // ---------- DASHBOARD MODULES ----------
    const funnelDefs = [['Inquiry', 1.95, 7], ['Viewing', 1.55, 5], ['Negotiation', 1.20, 3], ['Reservation', 0.90, 3], ['Documentation', 0.45, 2], ['Handover', 0.30, 1]];
    const funnel = funnelDefs.map(([stage, val, c]) => ({ stage, value: fmtB(val), count: c, bar: `height:100%;width:${Math.round(val / 1.95 * 100)}%;border-radius:6px;background:linear-gradient(90deg,var(--navy),#2E4870);` }));
    const refDefs = [['Alh. Sani Bello', 820], ['Eng. Grace Okoro', 610], ['Dr. M. Abubakar', 450], ['Hajiya Z. Lawan', 380]];
    const referrers = refDefs.map(([name, val]) => ({ name, value: '₦' + val + 'M', initials: name.replace(/^(Alh\.|Eng\.|Dr\.|Hajiya|Chief|Mrs\.)\s*/, '').split(' ').map((x) => x[0]).slice(0, 2).join('').toUpperCase(), bar: `height:100%;width:${Math.round(val / 820 * 100)}%;border-radius:4px;background:linear-gradient(90deg,var(--goldb),var(--gold));` }));

    const inventory = [
        { label: 'Sold', units: 9, value: '₦1.20B', dot: 'width:9px;height:9px;border-radius:3px;background:var(--navy);', seg: `width:${9 / 24 * 100}%;background:var(--navy);` },
        { label: 'Reserved', units: 4, value: '₦0.53B', dot: 'width:9px;height:9px;border-radius:3px;background:var(--gold);', seg: `width:${4 / 24 * 100}%;background:var(--gold);` },
        { label: 'Available', units: 11, value: '₦1.47B', dot: 'width:9px;height:9px;border-radius:3px;background:var(--steel);', seg: `width:${11 / 24 * 100}%;background:var(--steel);` },
    ];

    const maxBudget = 5.4;
    const buildPerf = projectDefs.map((p) => {
        const over = p.spent > p.budget;
        return {
            name: p.name.length > 26 ? p.name.slice(0, 24) + '…' : p.name,
            note: over ? '+8% over' : p.margin + ' margin',
            flag: over ? 'font-size:10.5px;font-weight:700;color:var(--alert);background:var(--alerts);padding:2px 8px;border-radius:20px;' : 'font-size:11px;color:var(--t2);',
            budgetBar: `position:absolute;left:0;top:0;height:100%;width:${Math.round(p.budget / maxBudget * 100)}%;background:var(--card2);border-right:1px dashed var(--border);`,
            spentBar: `position:absolute;left:0;top:0;height:100%;width:${Math.round(p.spent / maxBudget * 100)}%;border-radius:5px;background:${over ? 'linear-gradient(90deg,var(--alert),#C98C72)' : 'linear-gradient(90deg,var(--goldb),var(--gold))'};`,
        };
    });

    const stepIcon = { done: 'check', active: 'autorenew', overdue: 'priority_high', todo: '' };
    const stepDot = (st) => {
        const base = 'width:24px;height:24px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;';
        if (st === 'done') return base + 'background:var(--gold);color:#fff;';
        if (st === 'active') return base + 'background:var(--navy);color:#E3CB97;';
        if (st === 'overdue') return base + 'background:var(--alerts);color:var(--alert);border:1.5px solid var(--alert);';
        return base + 'background:var(--card2);color:var(--t3);border:1.5px solid var(--border);';
    };
    const stepLine = (done) => `flex:1;height:2px;background:${done ? 'var(--gold)' : 'var(--line)'};`;
    const hoDefs = [
        { unit: 'Unit C04', buyer: 'Alh. Sani Bello', steps: ['done', 'done', 'active', 'todo'], note: 'Title processing', ok: true },
        { unit: 'Unit C07', buyer: 'Dr. M. Abubakar', steps: ['done', 'overdue', 'todo', 'todo'], note: 'Payment overdue 14d', ok: false },
        { unit: 'Unit B11', buyer: 'Eng. Grace Okoro', steps: ['done', 'done', 'done', 'done'], note: 'Handed over', ok: true },
        { unit: 'Unit A02', buyer: 'Hajiya Z. Lawan', steps: ['done', 'active', 'todo', 'todo'], note: 'On schedule', ok: true },
    ];
    const handover = hoDefs.map((h) => ({
        unit: h.unit, buyer: h.buyer, note: h.note,
        flag: h.ok ? 'font-size:10.5px;font-weight:700;color:var(--green);background:var(--greens);padding:2px 9px;border-radius:20px;' : 'font-size:10.5px;font-weight:700;color:var(--alert);background:var(--alerts);padding:2px 9px;border-radius:20px;',
        steps: h.steps.map((st, i) => ({ dot: stepDot(st), icon: stepIcon[st], line: i < 3 ? stepLine(st === 'done') : 'display:none;' })),
    }));

    const tStage = { Prequalified: ['var(--steels)', 'var(--steel)'], 'Bid submitted': ['var(--ambers)', 'var(--amber)'], Shortlisted: ['var(--greens)', 'var(--green)'] };
    const tenderDefs = [['Nigerian Navy Housing Scheme', 'Nigerian Navy', '₦6.2B', 'Bid submitted'], ['Federal Secretariat Retrofit, Abuja', 'FCTA', '₦3.6B', 'Shortlisted'], ['Nasarawa Rural Electrification', 'REA', '₦2.8B', 'Prequalified']];
    const tenders = tenderDefs.map(([name, client, value, stage]) => { const [bg, c] = tStage[stage]; return { name, client, value, stage, stageStyle: `font-size:10.5px;font-weight:700;padding:3px 10px;border-radius:20px;background:${bg};color:${c};` }; });

    const naIcon = (bg, c) => `width:34px;height:34px;flex:none;border-radius:9px;background:${bg};display:flex;align-items:center;justify-content:center;color:${c};`;
    const needsAttention = [
        ['account_tree', 'var(--alerts)', 'var(--alert)', 'FCT Satellite Town Road Network — 8% over budget', 'Cost to date ₦2.05B against ₦1.9B contract value', 'Review costs'],
        ['solar_power', 'var(--alerts)', 'var(--alert)', 'Keffi Solar Mini-Grid delayed', 'Panel delivery milestone overdue — supplier escalation needed', 'Escalate'],
        ['request_quote', 'var(--alerts)', 'var(--alert)', 'FCTA invoice ₦0.60B overdue 90+ days', 'Road network certificate payment outstanding', 'Chase payment'],
        ['description', 'var(--ambers)', 'var(--amber)', 'Unit C07 documentation stalled', 'Dr. M. Abubakar — payment overdue 14 days', 'Follow up'],
        ['trending_down', 'var(--ambers)', 'var(--amber)', 'Katampe enquiry quiet for 21 days', 'No contact since site viewing — re-engage prospect', 'Re-engage'],
    ].map(([icon, bg, c, title, sub, action]) => ({ icon, iconWrap: naIcon(bg, c), title, sub, action }));

    // ---------- BUILD ----------
    const buildProjects = projectDefs.map((p) => {
        const done = Math.round(p.pct / 100 * 9);
        return {
            ...p, value: fmtB(p.value), pct: p.pct + '%', budget: fmtB(p.budget), spent: fmtB(p.spent),
            dot: `width:9px;height:9px;border-radius:50%;background:${hColor[p.health]};flex:none;`,
            bar: bar(p.pct), onOpen: () => openProj(p),
            stages: stageNames.map((sn, i) => {
                const st = i < done ? 'done' : (i === done ? 'active' : 'todo');
                const sty = st === 'done' ? 'background:var(--golds);color:var(--gold);border:1px solid transparent;' : st === 'active' ? 'background:var(--navy);color:#E3CB97;border:1px solid transparent;' : 'background:transparent;color:var(--t3);border:1px solid var(--border);';
                return { name: sn, style: `font-size:11px;font-weight:600;padding:5px 11px;border-radius:20px;${sty}` };
            }),
        };
    });

    const pjDef = s.selProj || projectDefs[0];
    const remain = (b, sp2) => '₦' + (Math.round((b - sp2) * 100) / 100) + 'B';
    const pjDone = Math.round(pjDef.pct / 100 * 9);
    const pd = {
        name: pjDef.name, engineer: pjDef.engineer, pct: pjDef.pct + '%', bar: bar(pjDef.pct), timeline: pjDef.timeline,
        stageLabel: 'Current stage · ' + stageNames[Math.min(pjDone, 8)],
        stats: [['Budget', fmtB(pjDef.budget)], ['Spent', fmtB(pjDef.spent)], ['Remaining', remain(pjDef.budget, pjDef.spent)], ['Stages', pjDone + ' / 9']].map(([k, val]) => ({ k, v: val })),
        stages: stageNames.map((sn, i) => {
            const st = i < pjDone ? 'done' : (i === pjDone ? 'active' : 'todo');
            const pctv = st === 'done' ? 100 : (st === 'active' ? 35 : 0);
            const dot = st === 'done' ? 'width:15px;height:15px;border-radius:50%;background:var(--gold);box-shadow:0 0 0 4px var(--golds);flex:none;' : st === 'active' ? 'width:15px;height:15px;border-radius:50%;background:var(--navy);box-shadow:0 0 0 4px var(--golds);flex:none;animation:softPulse 1.6s ease-in-out infinite;' : 'width:15px;height:15px;border-radius:50%;background:var(--card2);border:2px solid var(--border);flex:none;';
            const chip = st === 'done' ? 'font-size:10.5px;font-weight:700;padding:3px 10px;border-radius:20px;background:var(--greens);color:var(--green);' : st === 'active' ? 'font-size:10.5px;font-weight:700;padding:3px 10px;border-radius:20px;background:var(--navy);color:#E3CB97;' : 'font-size:10.5px;font-weight:700;padding:3px 10px;border-radius:20px;background:var(--card2);color:var(--t3);';
            const statusLabel = st === 'done' ? 'Done' : (st === 'active' ? 'In progress' : 'Scheduled');
            const note = st === 'done' ? 'Inspected & signed off' : (st === 'active' ? 'Crews on site now' : 'Awaiting predecessor');
            return { name: sn, dot, chip, statusLabel, note, pct: pctv + '%', bar: bar(pctv), hasPhoto: st !== 'todo', slot: 'stg-' + pjDef.name.replace(/[^a-z]/gi, '') + '-' + i, photo: SITE_PHOTOS[i % SITE_PHOTOS.length] };
        }),
    };

    // ---------- CLIENTS ----------
    const clients = [
        { name: 'Alh. Sani Bello', initials: 'SB', interest: 'Bankrol Heights · Unit C04', budget: '₦135M', status: 'Purchased', exec: 'Adunni Okafor', ref: 'Direct' },
        { name: 'Dr. M. Abubakar', initials: 'MA', interest: 'Bankrol Heights · Unit C07', budget: '₦140M', status: 'Documentation', exec: 'Grace Eze', ref: 'Alh. Sani Bello' },
        { name: 'Eng. Grace Okoro', initials: 'GO', interest: 'Bankrol Heights · Unit B11', budget: '₦128M', status: 'Purchased', exec: 'Tunde Bakare', ref: 'Direct' },
        { name: 'Hajiya Z. Lawan', initials: 'ZL', interest: 'Bankrol Heights · Unit A02', budget: '₦150M', status: 'Reserved', exec: 'Adunni Okafor', ref: 'Dr. M. Abubakar' },
        { name: 'Chief Okonkwo', initials: 'CO', interest: 'Bankrol Heights · Unit B05', budget: '₦132M', status: 'Negotiation', exec: 'Grace Eze', ref: 'Alh. Sani Bello' },
        { name: 'Zenith Holdings', initials: 'ZH', interest: 'Katampe Mixed-Use Block', budget: '₦0.9B', status: 'Lead', exec: 'Tunde Bakare', ref: 'Direct' },
    ];
    const clientFilters = ['All', 'Lead', 'Negotiation', 'Reserved', 'Documentation', 'Purchased'].map((f) => ({ label: f, onClick: () => set({ clientFilter: f }), style: `font:600 12px 'Hanken Grotesk',sans-serif;padding:8px 15px;border-radius:30px;cursor:pointer;transition:all .2s ease;border:1px solid ${s.clientFilter === f ? 'var(--gold)' : 'var(--border)'};background:${s.clientFilter === f ? 'var(--golds)' : 'var(--card)'};color:${s.clientFilter === f ? 'var(--gold)' : 'var(--t2)'};` }));
    const filteredClients = clients.filter((c) => s.clientFilter === 'All' || c.status === s.clientFilter).map((c) => ({ ...c, badge: statusBadge(c.status), onView: () => { set({ screen: 'client-profile', selClient: c }); window.scrollTo(0, 0); } }));

    const sc2 = s.selClient || clients[0];
    const cp = {
        initials: sc2.initials, name: sc2.name, status: sc2.status, badge: statusBadge(sc2.status),
        info: [['Interested Unit', sc2.interest], ['Budget', sc2.budget], ['Assigned Exec', sc2.exec], ['Referred By', sc2.ref]].map(([k, val]) => ({ k, v: val })),
        timeline: [['Enquiry received', '12 May 2026'], ['Site viewing — ' + sc2.interest, '19 May 2026'], ['Offer letter issued', '28 May 2026'], ['Reservation deposit paid', '06 Jun 2026']].map(([title, date]) => ({ title, date })),
        payments: [['Reservation deposit', '06 Jun 2026', '₦45M'], ['First instalment', '18 Jun 2026', '₦60M'], ['Balance due', '—', '₦30M']].map(([label, date, amount]) => ({ label, date, amount })),
        comms: [['call', 'Call — discussed payment plan', '2 days ago'], ['mail', 'Email — sent allocation letter', '5 days ago'], ['chat', 'WhatsApp — viewing confirmation', '1 week ago']].map(([icon, title, date]) => ({ icon, title, date })),
    };

    // ---------- DOCS ----------
    const docCtx = { name: 'Bankrol Heights · Unit C04', dev: 'Bankrol Heights, Abuja', price: '₦135M', land: '520 m²', beds: 4, engineer: 'Engr. Musa Ibrahim', received: '₦95M', buyer: 'Alh. Sani Bello' };
    const docClient = (s.selClient && s.selClient.name) || docCtx.buyer;
    const docTypeMap = { 'Sales Agreement': 'Deed of Sale & Sales Agreement', 'Offer Letter': 'Letter of Offer', 'Allocation Letter': 'Letter of Allocation', Invoice: 'Invoice', Receipt: 'Official Receipt', 'Completion Certificate': 'Certificate of Completion' };
    const dt = s.docType || 'Sales Agreement';
    const doc = {
        title: dt, heading: docTypeMap[dt] || dt, ref: 'BNK/2026/0142', date: '23 June 2026',
        client: docClient, property: docCtx.name, dev: docCtx.dev, price: docCtx.price, land: docCtx.land, beds: docCtx.beds, engineer: docCtx.engineer, amount: docCtx.received,
        schedule: [['Reservation deposit', '10% — on offer'], ['First instalment', '40% — on allocation'], ['Balance', '50% — before handover']].map(([k, val]) => ({ k, v: val })),
        allocation: [['Unit', docCtx.name], ['Development', docCtx.dev], ['Land Size', docCtx.land], ['Allocation Date', '23 June 2026']].map(([k, val]) => ({ k, v: val })),
        lineItems: [['Unit purchase — ' + docCtx.name, docCtx.price], ['Legal & documentation', '₦2.5M'], ['Service charge (1 year)', '₦1.8M']].map(([k, val]) => ({ k, v: val })),
        receipt: [['Received From', docClient], ['Being Payment For', docCtx.name], ['Payment Method', 'Bank Transfer'], ['Date', '23 June 2026'], ['Receipt No', 'BNK/RC/0312']].map(([k, val]) => ({ k, v: val })),
        completion: [['Completion Date', '23 June 2026'], ['Certificate No', 'BNK/CC/0088'], ['Standard', 'FCDA Approved']].map(([k, val]) => ({ k, v: val })),
        isAgreement: dt === 'Sales Agreement', isOffer: dt === 'Offer Letter', isAllocation: dt === 'Allocation Letter', isInvoice: dt === 'Invoice', isReceipt: dt === 'Receipt', isCompletion: dt === 'Completion Certificate',
    };
    // Payment Receipt template data (Bankrol demo context)
    const rcpt = {
        receiptNo: 'BNK/RC/0312',
        txnId: 'PAY-9F2C-7K10',
        amountPaid: '₦95,000,000',
        paidFor: 'Toward ' + docCtx.name + ' — installment payment, Bankrol Heights, Abuja.',
        payer: docClient,
        payerLine: 'Bankrol Heights, Abuja · FCT, Nigeria',
        datePaid: '23 June 2026 · 14:32',
        payMethod: 'Bank transfer · Zenith ••4471',
        invoiceTotal: '₦95,000,000',
        balance: '₦0.00',
        note: 'This document confirms the amount above has been received and applied to your purchase ledger. No further action is required for this installment. Please retain this receipt for your records.',
        signature: 'A. Okafor',
        signatureRole: 'Authorised Signatory · Finance',
        footerLeft: 'Bankrol Limited · RC 1029384 · finance@bankrol.ng · +234 9 876 5432',
        footerRight: 'Thank you',
        items: [
            { title: docCtx.name + ' — installment payment', amount: '₦92,700,000' },
            { title: 'Infrastructure & service levy', amount: '₦1,500,000' },
            { title: 'Documentation & title processing', amount: '₦800,000' },
        ],
    };
    // Invoice template data (Bankrol demo context)
    const inv = {
        invoiceNo: doc.ref,
        reference: 'PO-2026-0142',
        clientName: docClient,
        clientLine1: 'Bankrol Heights, Abuja',
        clientLine2: 'FCT, Nigeria',
        dateIssued: doc.date,
        payMethod: 'Zenith Bank · 1023 4856 91',
        taxLabel: 'VAT (7.5%)',
        notes: 'Payment due within 14 days of the date issued. Kindly quote the reference above on all transfers. Account: Bankrol Limited · Zenith Bank · 1023 4856 91.',
        signature: 'A. Okafor',
        signatureRole: 'Authorised Signatory · Finance',
        footerLeft: 'Bankrol Limited · RC 1029384 · finance@bankrol.ng · +234 9 876 5432',
        footerRight: 'Thank you',
        subtotal: '₦139,300,000',
        tax: '₦10,447,500',
        total: '₦149,747,500',
        items: [
            { title: docCtx.name, detail: '4-bed terrace · Bankrol Heights development', qty: '1', unit: '₦135,000,000', amount: '₦135,000,000' },
            { title: 'Legal & documentation', detail: 'Deed of assignment · Governor’s consent', qty: '1', unit: '₦2,500,000', amount: '₦2,500,000' },
            { title: 'Service charge (1 year)', detail: 'Estate management & security', qty: '1', unit: '₦1,800,000', amount: '₦1,800,000' },
        ],
    };
    // Sale & Purchase Agreement template data (Bankrol demo context)
    const agStatusFg = { Paid: '#5E7355', Due: '#B07C1E', Scheduled: '#9A958B' };
    const agr = {
        brand: 'BANKROL',
        instrument: 'Deed of Sale · Private & Confidential',
        agreementNo: 'BNK/SPA/0142',
        agreementDate: doc.date,
        totalPrice: '₦135,000,000',
        totalWords: 'One hundred and thirty-five million naira only',
        completionDate: '30 September 2026',
        interestRate: '1.5%',
        cureDays: '21 days',
        forfeit: '10%',
        jurisdiction: 'Federal Republic of Nigeria',
        venue: 'Abuja',
        showWatermark: false,
        vendorName: 'Bankrol Limited',
        vendorLine1: 'Plot 124, Cadastral Zone B05, Utako',
        vendorLine2: 'Abuja, FCT · Nigeria',
        vendorMeta: 'RC 1029384 · acting by its duly authorised officer',
        vendorSign: 'A. Okafor',
        vendorSignRole: 'Managing Director, for and on behalf of the Vendor',
        purchaserName: docClient,
        purchaserLine1: 'Bankrol Heights, Abuja',
        purchaserLine2: 'FCT · Nigeria',
        purchaserMeta: 'In their personal capacity',
        footerLeft: 'Bankrol Limited · RC 1029384 · legal@bankrol.ng · +234 9 876 5432',
        schedule: [
            { n: '1', title: 'Deposit on signing', due: 'On execution of this Agreement', amount: '₦13,500,000', status: 'Paid' },
            { n: '2', title: 'Second instalment', due: 'On or before 31 July 2026', amount: '₦40,500,000', status: 'Paid' },
            { n: '3', title: 'Third instalment', due: 'On or before 31 August 2026', amount: '₦40,500,000', status: 'Due' },
            { n: '4', title: 'Balance on completion', due: 'On or before 30 September 2026', amount: '₦40,500,000', status: 'Scheduled' },
        ].map((r) => ({ ...r, statusFg: agStatusFg[r.status] || '#9A958B' })),
    };
    const docSteps = [['1', 'Select Client'], ['2', 'Select Unit'], ['3', 'Document Type'], ['4', 'Generate'], ['5', 'Preview'], ['6', 'Download PDF']].map(([n, label]) => ({ n, label }));
    const docCards = [['gavel', 'Sales Agreement', 'Binding contract between buyer and Bankrol.'], ['mail', 'Offer Letter', 'Formal offer with price and terms.'], ['assignment', 'Allocation Letter', 'Confirms unit allocation to a buyer.'], ['receipt_long', 'Invoice', 'Itemised statement of amounts due.'], ['paid', 'Receipt', 'Proof of a payment received.'], ['workspace_premium', 'Completion Certificate', 'Certifies a finished, handed-over unit.']].map(([icon, name, desc]) => ({ icon, name, desc, onOpen: () => openDoc(name) }));

    // ---------- FINANCE ----------
    const financeKpis = [['Active Order Book', '₦14.2B', '7 active projects'], ['Receivables', '₦3.1B', '₦0.6B overdue 90+ days'], ['Payables', '₦1.4B', 'suppliers & subcontractors'], ['Net Cash', '₦1.7B', 'receivable less payable']].map(([label, value, note]) => ({ label, value, note }));
    const ageDefs = [['0–30 days', 1.62, 'var(--green)', '', false], ['31–60 days', 0.58, 'var(--gold)', '', false], ['61–90 days', 0.30, 'var(--amber)', '', false], ['90+ days', 0.60, 'var(--alert)', 'Overdue', true]];
    const ageing = ageDefs.map(([bucket, val, col, note, flag]) => ({ bucket, value: fmtB(val), note, bar: `height:100%;width:${Math.round(val / 1.62 * 100)}%;border-radius:5px;background:${col};`, flag: flag ? 'font-size:9.5px;font-weight:700;color:var(--alert);background:var(--alerts);padding:1px 7px;border-radius:20px;' : 'display:none;' }));
    const payments = [
        ['Alh. Sani Bello', 'Bankrol Heights · C04', '₦60M', 'Bank Transfer', '23 Jun 2026'],
        ['Nigerian Navy', 'Naval Base · Phase 2 cert', '₦400M', 'Bank Transfer', '21 Jun 2026'],
        ['Eng. Grace Okoro', 'Bankrol Heights · B11', '₦128M', 'Bank Transfer', '18 Jun 2026'],
        ['FCTA', 'Road Network · Cert 4', '₦220M', 'Bank Transfer', '12 Jun 2026'],
    ].map(([client, prop, amount, method, date]) => ({ client, prop, amount, method, date }));

    // ---------- REPORTS ----------
    const reportTabs = ['Sales', 'Construction', 'Finance', 'Tenders'].map((t) => ({ label: t, onClick: () => set({ reportTab: t }), style: pillStyle(s.reportTab === t) }));
    const costBars = [['Q1', 40], ['Q2', 62], ['Q3', 55], ['Q4', 78], ['Q1', 68], ['Q2', 90]].map(([label, h]) => ({ label, style: `width:100%;height:${h}%;border-radius:6px 6px 0 0;background:linear-gradient(180deg,var(--goldb),var(--gold));` }));
    const salesBars = [['Infrastructure', '₦6.7B', 100], ['Construction', '₦6.3B', 94], ['Estates', '₦3.2B', 48], ['Energy', '₦2.1B', 31]].map(([name, value, w]) => ({ name, value, bar: bar(w) }));

    return {
        theme, isLogin: !s.authed, isApp: s.authed,
        themeIcon: theme === 'dark' ? 'light_mode' : 'dark_mode', themeLabel: theme === 'dark' ? 'Light mode' : 'Dark mode',
        signIn, toggleTheme,
        navItems, mobileNav, pageTitle: pt[0], pageSub: pt[1], periodLabel: periodLabels[s.period] || 'This Month',
        isDashboard: scr === 'dashboard', isPortfolio: scr === 'portfolio',
        isClients: scr === 'clients', isClientProfile: scr === 'client-profile', isBuild: scr === 'build', isBuildDetail: scr === 'build-detail',
        isFinance: scr === 'finance', isDocuments: scr === 'documents', isReports: scr === 'reports',
        periods, showReferral,
        portFilters, portFiltersWide, filteredProjects,
        funnel, referrers, inventory, buildPerf, handover, tenders, needsAttention,
        drawerOpen: s.drawerOpen, dr, closeDrawer, stop,
        buildProjects, pd, backToBuild: () => go('build'),
        clientFilters, filteredClients, cp, backToClients: () => go('clients'),
        financeKpis, ageing, payments,
        docSteps, docCards, reportTabs, costBars, salesBars,
        moreOpen: s.moreOpen, moreItems, closeMore,
        docOpen: s.docOpen, doc, rcpt, inv, agr, closeDoc, printDoc, genReceipt,
    };
});
</script>

<template>
    <Head title="Bankrol Platform">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Hanken+Grotesk:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0&display=block" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Sharp:opsz,wght,FILL,GRAD@24,300,0,0&display=block" />
    </Head>

    <div :data-theme="state.theme" class="bnk-root" style="min-height:100vh;background:var(--grad),var(--bg);font-family:'Hanken Grotesk',sans-serif;color:var(--text);transition:background .4s ease,color .4s ease;">

        <!-- ===================== LOGIN ===================== -->
        <template v-if="v.isLogin">
            <div style="min-height:100vh;display:grid;grid-template-columns:1.1fr .9fr;" class="bnk-login-grid">
                <div id="login-art" style="position:relative;background:#1C2E4A;display:flex;align-items:flex-end;padding:48px;overflow:hidden;">
                    <BnkImageSlot id="login-hero" :src="HERO_PHOTO" placeholder="Estate or project photograph" style="position:absolute;inset:0;" />
                    <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(10,11,13,.18),rgba(10,11,13,.62));pointer-events:none;"></div>
                    <div style="position:relative;z-index:1;max-width:460px;">
                        <div style="font-family:'Cormorant Garamond',serif;font-weight:500;font-size:clamp(30px,4vw,46px);line-height:1.12;color:#F4F1EA;">We develop, build and sell — then hand you the keys.</div>
                        <div style="font-size:14px;color:rgba(244,241,234,.7);margin-top:18px;line-height:1.6;">Construction, investment and energy. Develop → Build → Sell → Handover, in one operating view.</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;justify-content:center;padding:40px 28px;">
                    <div style="width:100%;max-width:380px;">
                        <div style="margin-bottom:40px;">
                            <img :src="state.theme === 'dark' ? LOGO_DARK : LOGO_LIGHT" alt="Bankrol — Construction, Investment, Energy" style="height:48px;width:auto;max-width:100%;display:block;" />
                        </div>
                        <div style="font-family:'Cormorant Garamond',serif;font-size:30px;font-weight:600;color:var(--text);">Welcome back</div>
                        <div style="font-size:13.5px;color:var(--t2);margin-top:6px;">Sign in to your executive command centre</div>

                        <div style="margin-top:30px;">
                            <label style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--t3);">Email</label>
                            <input type="email" value="director@bankrol.ng" style="width:100%;margin-top:8px;padding:14px 15px;background:var(--card);border:1px solid var(--border);border-radius:12px;font:500 14px 'Hanken Grotesk',sans-serif;color:var(--text);outline:none;" />
                        </div>
                        <div style="margin-top:18px;">
                            <label style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--t3);">Password</label>
                            <input type="password" value="bankrol" style="width:100%;margin-top:8px;padding:14px 15px;background:var(--card);border:1px solid var(--border);border-radius:12px;font:500 14px 'Hanken Grotesk',sans-serif;color:var(--text);outline:none;" />
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:18px;">
                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--t2);cursor:pointer;"><span style="width:18px;height:18px;border-radius:5px;border:1px solid var(--gold);background:var(--golds);display:inline-flex;align-items:center;justify-content:center;color:var(--gold);"><span style="font-family:'Material Symbols Rounded';font-size:13px;">check</span></span>Remember me</label>
                            <a style="font-size:13px;color:var(--gold);text-decoration:none;cursor:pointer;">Forgot password?</a>
                        </div>
                        <button @click="v.signIn" style="width:100%;margin-top:26px;padding:15px;background:var(--navy);color:#fff;border:none;border-radius:12px;font:600 14px 'Hanken Grotesk',sans-serif;letter-spacing:.02em;cursor:pointer;box-shadow:var(--shadow);">Sign In</button>
                        <button @click="v.toggleTheme" style="width:100%;margin-top:12px;padding:11px;background:transparent;color:var(--t2);border:1px solid var(--border);border-radius:12px;font:600 12px 'Hanken Grotesk',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;"><span style="font-family:'Material Symbols Rounded';font-size:16px;">{{ v.themeIcon }}</span>{{ v.themeLabel }}</button>
                    </div>
                </div>
            </div>
        </template>

        <!-- ===================== APP SHELL ===================== -->
        <template v-if="v.isApp">
            <div id="shell">
                <!-- SIDEBAR -->
                <aside id="sidebar" style="position:sticky;top:0;height:100vh;display:flex;flex-direction:column;padding:24px 18px;border-right:1px solid var(--border);background:var(--card);">
                    <div style="padding:0 6px 22px;">
                        <img :src="state.theme === 'dark' ? LOGO_DARK : LOGO_LIGHT" alt="Bankrol — Construction, Investment, Energy" style="height:40px;width:auto;max-width:100%;display:block;" />
                    </div>
                    <nav style="display:flex;flex-direction:column;gap:3px;flex:1;">
                        <button v-for="(n, i) in v.navItems" :key="i" @click="n.onClick" :style="n.style"><span class="nav-ic" style="font-size:21px;">{{ n.icon }}</span><span style="font-size:13.5px;font-weight:600;">{{ n.label }}</span></button>
                    </nav>
                    <button @click="v.toggleTheme" style="display:flex;align-items:center;gap:11px;padding:11px 14px;border-radius:11px;border:1px solid var(--border);background:var(--card2);color:var(--t2);cursor:pointer;font:600 12.5px 'Hanken Grotesk',sans-serif;"><span style="font-family:'Material Symbols Rounded';font-size:18px;">{{ v.themeIcon }}</span>{{ v.themeLabel }}</button>
                </aside>

                <!-- CONTENT -->
                <main id="content" style="padding:24px 30px 48px;min-width:0;">
                    <!-- TOPBAR -->
                    <header style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:8px;">
                        <div>
                            <div style="font-family:'Cormorant Garamond',serif;font-weight:600;font-size:clamp(26px,3.4vw,34px);line-height:1;color:var(--text);">{{ v.pageTitle }}</div>
                            <div style="font-size:13px;color:var(--t2);margin-top:6px;">{{ v.pageSub }}</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div id="topbar-search" style="display:flex;align-items:center;gap:8px;padding:9px 14px;background:var(--card);border:1px solid var(--border);border-radius:11px;box-shadow:var(--shadow);"><span style="font-family:'Material Symbols Rounded';font-size:18px;color:var(--t3);">search</span><input placeholder="Search projects, clients…" style="border:none;background:transparent;outline:none;font:500 13px 'Hanken Grotesk',sans-serif;color:var(--text);width:200px;" /></div>
                            <button style="position:relative;width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:var(--card);border:1px solid var(--border);border-radius:11px;color:var(--t2);cursor:pointer;box-shadow:var(--shadow);"><span style="font-family:'Material Symbols Rounded';font-size:20px;">notifications</span><span style="position:absolute;top:-4px;right:-4px;min-width:17px;height:17px;padding:0 4px;display:flex;align-items:center;justify-content:center;background:var(--alert);color:#fff;font-size:10px;font-weight:700;border-radius:9px;border:2px solid var(--bg);">5</span></button>
                            <div style="display:flex;align-items:center;gap:10px;padding:4px 5px 4px 14px;background:var(--card);border:1px solid var(--border);border-radius:24px;box-shadow:var(--shadow);"><div style="text-align:right;line-height:1.15;"><div style="font-size:12px;font-weight:600;color:var(--text);">Adunni Okafor</div><div style="font-size:9.5px;color:var(--t3);">Managing Director</div></div><div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--navy),#28406B);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:14px;font-weight:600;color:var(--goldb);">AO</div></div>
                            <button @click="signOut" title="Sign out" aria-label="Sign out" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:var(--card);border:1px solid var(--border);border-radius:11px;color:var(--t2);cursor:pointer;box-shadow:var(--shadow);"><span style="font-family:'Material Symbols Rounded';font-size:20px;">logout</span></button>
                        </div>
                    </header>

                    <!-- ============ DASHBOARD ============ -->
                    <template v-if="v.isDashboard">
                        <div data-screen-label="Dashboard">
                            <div v-if="!state.hintDismissed" style="display:flex;align-items:center;gap:11px;margin:18px 0 6px;padding:11px 14px;background:var(--golds);border:1px solid rgba(168,133,78,.3);border-radius:12px;">
                                <span style="font-family:'Material Symbols Rounded';font-size:20px;color:var(--gold);flex:none;">touch_app</span>
                                <span style="flex:1;font-size:12.5px;line-height:1.45;color:var(--text);font-weight:600;">New here? Tap any <span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:var(--gold);color:#fff;vertical-align:middle;"><span style="font-family:'Material Symbols Rounded';font-size:12px;">info</span></span> icon to see what that number means.</span>
                                <button @click="dismissHint" style="flex:none;padding:7px 13px;background:var(--navy);color:#fff;border:none;border-radius:9px;font:600 12px 'Hanken Grotesk',sans-serif;cursor:pointer;">Got it</button>
                            </div>
                            <div style="display:flex;align-items:center;gap:3px;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:3px;width:max-content;margin:18px 0 4px;box-shadow:var(--shadow);">
                                <button v-for="(p, i) in v.periods" :key="i" @click="p.onClick" :style="p.style">{{ p.label }}</button>
                            </div>

                            <!-- KPI STRIP -->
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px;margin-top:18px;">
                                <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:24px 26px;box-shadow:var(--shadow);">
                                    <div style="display:flex;justify-content:space-between;align-items:center;"><span style="display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Active Order Book<BnkInfo text="Total value of all signed, in-progress work across construction, estates and energy — the company's confirmed workload not yet completed." /></span><span style="font-family:'Material Symbols Rounded';font-size:18px;color:var(--gold);">inventory_2</span></div>
                                    <div style="margin-top:16px;font-family:'Cormorant Garamond',serif;font-weight:600;font-size:clamp(36px,4.6vw,46px);line-height:1;color:var(--text);" data-countup data-value="14.2" data-prefix="₦" data-suffix="B" data-decimals="1">₦14.2B</div>
                                    <div style="font-size:12.5px;color:var(--t2);margin-top:12px;">7 active projects in progress</div>
                                </div>
                                <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:24px 26px;box-shadow:var(--shadow);">
                                    <div style="display:flex;justify-content:space-between;align-items:center;"><span style="display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Units Sold · {{ v.periodLabel }}<BnkInfo text="Value of homes sold and handed over in the selected period. Use the This Month / Quarter / Year toggle above to change the window. The +2 chip shows the change versus the previous period." /></span><span style="display:inline-flex;align-items:center;gap:2px;font-size:11px;font-weight:700;color:var(--green);background:var(--greens);padding:3px 8px 3px 5px;border-radius:20px;"><span style="font-family:'Material Symbols Rounded';font-size:13px;">arrow_upward</span>+2</span></div>
                                    <div style="margin-top:16px;font-family:'Cormorant Garamond',serif;font-weight:600;font-size:clamp(36px,4.6vw,46px);line-height:1;color:var(--text);" data-countup data-value="2.1" data-prefix="₦" data-suffix="B" data-decimals="1">₦2.1B</div>
                                    <div style="font-size:12.5px;color:var(--t2);margin-top:12px;">6 homes sold &amp; handed over</div>
                                </div>
                                <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:24px 26px;box-shadow:var(--shadow);">
                                    <div style="display:flex;justify-content:space-between;align-items:center;"><span style="display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Sales Pipeline<BnkInfo text="Value-weighted total of homes still in play — currently in negotiation or reservation, not yet closed. It's your forward revenue if the active deals convert." /></span><span style="font-family:'Material Symbols Rounded';font-size:18px;color:var(--gold);">filter_alt</span></div>
                                    <div style="margin-top:16px;font-family:'Cormorant Garamond',serif;font-weight:600;font-size:clamp(36px,4.6vw,46px);line-height:1;color:var(--text);" data-countup data-value="4.3" data-prefix="₦" data-suffix="B" data-decimals="1">₦4.3B</div>
                                    <div style="font-size:12.5px;color:var(--t2);margin-top:12px;">homes in negotiation &amp; reservation</div>
                                </div>
                                <div style="background:var(--navy);border:1px solid rgba(168,133,78,.3);border-radius:18px;padding:24px 26px;box-shadow:var(--shadow);">
                                    <div style="display:flex;justify-content:space-between;align-items:center;"><span style="display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(227,203,151,.7);">Net Cash Position<BnkInfo text="What's owed to you minus what you owe: ₦3.1B receivable less ₦1.4B payable. A quick read on liquidity across the whole business." /></span><span style="font-family:'Material Symbols Rounded';font-size:18px;color:#E3CB97;">account_balance</span></div>
                                    <div style="margin-top:16px;font-family:'Cormorant Garamond',serif;font-weight:600;font-size:clamp(36px,4.6vw,46px);line-height:1;color:#fff;" data-countup data-value="1.7" data-prefix="₦" data-suffix="B" data-decimals="1">₦1.7B</div>
                                    <div style="font-size:12.5px;color:rgba(244,241,234,.6);margin-top:12px;">₦3.1B receivable · ₦1.4B payable</div>
                                </div>
                            </div>

                            <!-- ESTATE & PROJECT PORTFOLIO -->
                            <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 26px;margin-top:18px;box-shadow:var(--shadow);">
                                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
                                    <div><span style="display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Estate &amp; Project Portfolio<BnkInfo text="Every active project across the business — develop, build, sell and handover. The coloured dot is project health (green on track, amber at risk, red delayed). Tap a row to open its detail drawer; filter by category with the chips on the right." /></span><div style="font-size:12px;color:var(--t2);margin-top:5px;">Develop · Build · Sell · Handover — everything in progress</div></div>
                                    <div style="display:flex;gap:7px;flex-wrap:wrap;">
                                        <button v-for="(f, i) in v.portFilters" :key="i" @click="f.onClick" :style="f.style">{{ f.label }}</button>
                                    </div>
                                </div>
                                <div style="overflow-x:auto;">
                                    <div style="min-width:720px;">
                                        <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1.7fr;gap:16px;padding:0 4px 11px;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--t3);border-bottom:1px solid var(--border);"><span>Project</span><span>Category</span><span>Value</span><span>Build Progress</span></div>
                                        <button v-for="(p, i) in v.filteredProjects" :key="i" class="bnk-rowhover" @click="p.onOpen" style="display:grid;grid-template-columns:2fr 1fr 1fr 1.7fr;gap:16px;align-items:center;width:100%;text-align:left;padding:15px 4px;border:none;border-bottom:1px solid var(--line);background:transparent;cursor:pointer;transition:background .2s ease;">
                                            <div style="display:flex;align-items:center;gap:11px;min-width:0;"><span :style="p.dot" :title="p.healthLabel"></span><div style="min-width:0;"><div style="font-size:13.5px;font-weight:600;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ p.name }}</div><div style="font-size:11.5px;color:var(--t2);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ p.client }}</div></div></div>
                                            <div><span :style="p.tagStyle">{{ p.tag }}</span></div>
                                            <div style="font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--navyt);">{{ p.value }}</div>
                                            <div><div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px;"><span style="font-size:11.5px;color:var(--t2);">{{ p.next }}</span><span style="font-size:12px;font-weight:700;color:var(--text);">{{ p.pct }}</span></div><div style="height:7px;background:var(--card2);border-radius:4px;overflow:hidden;"><div :style="p.bar"></div></div></div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- SALES PIPELINE + REFERRAL -->
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;margin-top:18px;">
                                <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 26px;box-shadow:var(--shadow);">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;"><div><span style="display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Sales Pipeline<BnkInfo text="The sales funnel for Bankrol Heights units, from first inquiry through to handover. Each bar is the value and unit count at that stage — the narrowing shows how deals progress toward closing." /></span><div style="font-size:12px;color:var(--t2);margin-top:5px;">Value-weighted · Bankrol Heights units</div></div><span style="font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--navyt);">₦4.3B</span></div>
                                    <div style="display:flex;flex-direction:column;gap:11px;">
                                        <div v-for="(f, i) in v.funnel" :key="i">
                                            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:5px;"><span style="font-size:12.5px;font-weight:600;color:var(--text);">{{ f.stage }}</span><span style="font-size:11.5px;color:var(--t2);"><span style="font-family:'Cormorant Garamond',serif;font-size:15px;color:var(--navyt);">{{ f.value }}</span> · {{ f.count }} units</span></div>
                                            <div style="height:11px;background:var(--card2);border-radius:6px;overflow:hidden;"><div :style="f.bar"></div></div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="v.showReferral" style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 26px;box-shadow:var(--shadow);">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;"><div><span style="display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Referral Intelligence<BnkInfo text="How your private network drives sales: the rate at which referrals convert to closed deals, total value closed via referral this year, and the people sending you the most business." /></span><div style="font-size:12px;color:var(--t2);margin-top:5px;">Your network, with numbers on it</div></div></div>
                                    <div style="display:flex;align-items:center;gap:18px;margin:14px 0 18px;padding:14px 16px;background:var(--card2);border-radius:13px;">
                                        <div><div style="font-family:'Cormorant Garamond',serif;font-size:34px;line-height:1;color:var(--gold);">34%</div><div style="font-size:11px;color:var(--t2);margin-top:4px;">referral → close rate</div></div>
                                        <div style="width:1px;height:36px;background:var(--border);"></div>
                                        <div><div style="font-family:'Cormorant Garamond',serif;font-size:34px;line-height:1;color:var(--navyt);">₦2.26B</div><div style="font-size:11px;color:var(--t2);margin-top:4px;">closed via referral · YTD</div></div>
                                    </div>
                                    <div style="font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--t3);margin-bottom:12px;">Top referrers by closed value</div>
                                    <div style="display:flex;flex-direction:column;gap:14px;">
                                        <div v-for="(r, i) in v.referrers" :key="i" style="display:flex;align-items:center;gap:12px;"><span style="width:30px;height:30px;flex:none;border-radius:50%;background:var(--golds);color:var(--gold);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;">{{ r.initials }}</span><div style="flex:1;min-width:0;"><div style="display:flex;justify-content:space-between;margin-bottom:5px;"><span style="font-size:12.5px;font-weight:600;color:var(--text);">{{ r.name }}</span><span style="font-family:'Cormorant Garamond',serif;font-size:15px;color:var(--navyt);">{{ r.value }}</span></div><div style="height:6px;background:var(--card2);border-radius:4px;overflow:hidden;"><div :style="r.bar"></div></div></div></div>
                                    </div>
                                </div>
                            </div>

                            <!-- INVENTORY + BUILD PERFORMANCE -->
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;margin-top:18px;">
                                <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 26px;box-shadow:var(--shadow);">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;"><div><span style="display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Inventory · Bankrol Heights<BnkInfo text="The 24 units in the estate broken down by status — sold, reserved and still available — with the value of each band. A fast view of what is left to sell." /></span><div style="font-size:12px;color:var(--t2);margin-top:5px;">24 units · what's left to sell</div></div></div>
                                    <div style="display:flex;height:20px;border-radius:7px;overflow:hidden;margin-bottom:18px;">
                                        <div v-for="(i, idx) in v.inventory" :key="idx" :style="i.seg" :title="i.label"></div>
                                    </div>
                                    <div style="display:flex;flex-direction:column;gap:12px;">
                                        <div v-for="(i, idx) in v.inventory" :key="idx" style="display:flex;align-items:center;justify-content:space-between;"><div style="display:flex;align-items:center;gap:9px;"><span :style="i.dot"></span><span style="font-size:13px;font-weight:600;color:var(--text);">{{ i.label }}</span><span style="font-size:12px;color:var(--t2);">{{ i.units }} units</span></div><span style="font-family:'Cormorant Garamond',serif;font-size:17px;color:var(--navyt);">{{ i.value }}</span></div>
                                    </div>
                                </div>
                                <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 26px;box-shadow:var(--shadow);">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;"><div><span style="display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Build Performance<BnkInfo text="Cost-to-date against the contract budget for each project. The grey bar is the budget, the gold bar is spent so far. A red marker flags projects running over budget." /></span><div style="font-size:12px;color:var(--t2);margin-top:5px;">Cost-to-date vs contract budget</div></div><div style="display:flex;align-items:center;gap:12px;font-size:10.5px;color:var(--t2);"><span style="display:flex;align-items:center;gap:5px;"><span style="width:9px;height:9px;border-radius:2px;background:var(--card2);border:1px solid var(--border);"></span>Budget</span><span style="display:flex;align-items:center;gap:5px;"><span style="width:9px;height:9px;border-radius:2px;background:var(--gold);"></span>Spent</span></div></div>
                                    <div style="display:flex;flex-direction:column;gap:13px;">
                                        <div v-for="(b, i) in v.buildPerf" :key="i">
                                            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px;"><span style="font-size:12.5px;font-weight:600;color:var(--text);">{{ b.name }}</span><span :style="b.flag">{{ b.note }}</span></div>
                                            <div style="position:relative;height:14px;background:var(--card2);border-radius:5px;overflow:hidden;"><div :style="b.budgetBar"></div><div :style="b.spentBar"></div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- HANDOVER TRACKER + BID PIPELINE -->
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(330px,1fr));gap:18px;margin-top:18px;">
                                <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 26px;box-shadow:var(--shadow);">
                                    <div style="margin-bottom:18px;"><span style="display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Handover &amp; Documentation<BnkInfo text="Tracks each sold unit through the four closing steps: Allocation → Payment → Title / C of O → Handover. Green means on track; red flags a step that is overdue and needs attention." /></span><div style="font-size:12px;color:var(--t2);margin-top:5px;">Allocation → Payment → Title → Handover</div></div>
                                    <div v-for="(h, i) in v.handover" :key="i" style="padding:13px 0;border-bottom:1px solid var(--line);">
                                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:11px;"><div><span style="font-size:13px;font-weight:600;color:var(--text);">{{ h.unit }}</span><span style="font-size:11.5px;color:var(--t2);"> · {{ h.buyer }}</span></div><span :style="h.flag">{{ h.note }}</span></div>
                                        <div style="display:flex;align-items:center;">
                                            <template v-for="(st, j) in h.steps" :key="j"><span :style="st.dot"><span style="font-family:'Material Symbols Rounded';font-size:12px;">{{ st.icon }}</span></span><span :style="st.line"></span></template>
                                        </div>
                                        <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:9.5px;color:var(--t3);"><span>Allocation</span><span>Payment</span><span>Title / C of O</span><span>Handover</span></div>
                                    </div>
                                </div>
                                <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 26px;box-shadow:var(--shadow);">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;"><div><span style="display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Bid &amp; Tender Pipeline<BnkInfo text="Contracting and energy opportunities you are bidding for, with their value and stage (prequalified, bid submitted, shortlisted). The win rate is the share of past bids won." /></span><div style="font-size:12px;color:var(--t2);margin-top:5px;">Contracting &amp; energy opportunities</div></div><div style="text-align:right;"><div style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--gold);line-height:1;">38%</div><div style="font-size:10px;color:var(--t2);margin-top:3px;">win rate</div></div></div>
                                    <div style="display:flex;flex-direction:column;gap:11px;">
                                        <div v-for="(t, i) in v.tenders" :key="i" style="padding:14px 16px;background:var(--card2);border:1px solid var(--border);border-radius:13px;"><div style="display:flex;align-items:center;justify-content:space-between;gap:10px;"><span style="font-size:13px;font-weight:600;color:var(--text);">{{ t.name }}</span><span style="font-family:'Cormorant Garamond',serif;font-size:18px;color:var(--navyt);white-space:nowrap;">{{ t.value }}</span></div><div style="display:flex;align-items:center;justify-content:space-between;margin-top:9px;"><span style="font-size:11.5px;color:var(--t2);">{{ t.client }}</span><span :style="t.stageStyle">{{ t.stage }}</span></div></div>
                                    </div>
                                </div>
                            </div>

                            <!-- NEEDS ATTENTION -->
                            <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 26px;margin-top:18px;box-shadow:var(--shadow);">
                                <div style="display:flex;align-items:center;gap:9px;margin-bottom:8px;"><span style="font-family:'Material Symbols Rounded';font-size:19px;color:var(--alert);">priority_high</span><span style="display:inline-flex;align-items:center;font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Needs Attention<BnkInfo text="The system's shortlist of issues to act on now — over-budget projects, delayed milestones, overdue invoices and stalled deals — each with a suggested next action." /></span></div>
                                <div v-for="(a, i) in v.needsAttention" :key="i" style="display:flex;align-items:center;gap:14px;padding:13px 2px;border-bottom:1px solid var(--line);"><span :style="a.iconWrap"><span style="font-family:'Material Symbols Rounded';font-size:18px;">{{ a.icon }}</span></span><div style="flex:1;min-width:0;"><div style="font-size:13.5px;font-weight:600;color:var(--text);">{{ a.title }}</div><div style="font-size:12px;color:var(--t2);margin-top:3px;">{{ a.sub }}</div></div><span style="font-size:11.5px;font-weight:700;color:var(--gold);white-space:nowrap;cursor:pointer;">{{ a.action }}</span></div>
                            </div>
                        </div>
                    </template>

                    <!-- ============ PORTFOLIO ============ -->
                    <template v-if="v.isPortfolio">
                        <div data-screen-label="Portfolio">
                            <div style="display:flex;gap:8px;flex-wrap:wrap;margin:20px 0 18px;">
                                <button v-for="(f, i) in v.portFiltersWide" :key="i" @click="f.onClick" :style="f.style">{{ f.label }}</button>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;">
                                <button v-for="(p, i) in v.filteredProjects" :key="i" class="bnk-cardhover" @click="p.onOpen" style="text-align:left;background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 24px;box-shadow:var(--shadow);cursor:pointer;">
                                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;"><div style="min-width:0;"><div style="display:flex;align-items:center;gap:8px;"><span :style="p.dot"></span><span :style="p.tagStyle">{{ p.tag }}</span></div><div style="font-size:15.5px;font-weight:600;color:var(--text);margin-top:11px;">{{ p.name }}</div><div style="font-size:12px;color:var(--t2);margin-top:3px;">{{ p.client }}</div></div><span style="font-family:'Cormorant Garamond',serif;font-size:21px;color:var(--navyt);white-space:nowrap;">{{ p.value }}</span></div>
                                    <div style="margin-top:16px;"><div style="display:flex;justify-content:space-between;font-size:11.5px;color:var(--t2);margin-bottom:6px;"><span>{{ p.next }}</span><span style="color:var(--text);font-weight:700;">{{ p.pct }}</span></div><div style="height:8px;background:var(--card2);border-radius:5px;overflow:hidden;"><div :style="p.bar"></div></div></div>
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:16px;padding-top:14px;border-top:1px solid var(--line);"><span :style="p.healthBadge">{{ p.healthLabel }}</span><span style="display:flex;align-items:center;gap:4px;font:600 12px 'Hanken Grotesk',sans-serif;color:var(--gold);">Open<span style="font-family:'Material Symbols Rounded';font-size:16px;">arrow_forward</span></span></div>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- ============ CLIENTS ============ -->
                    <template v-if="v.isClients">
                        <div data-screen-label="Clients">
                            <div style="display:flex;gap:8px;flex-wrap:wrap;margin:20px 0 18px;">
                                <button v-for="(f, i) in v.clientFilters" :key="i" @click="f.onClick" :style="f.style">{{ f.label }}</button>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
                                <button v-for="(c, i) in v.filteredClients" :key="i" class="bnk-cardhover" @click="c.onView" style="text-align:left;background:var(--card);border:1px solid var(--border);border-radius:18px;padding:20px;box-shadow:var(--shadow);cursor:pointer;">
                                    <div style="display:flex;align-items:center;gap:13px;"><span style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--navy),#28406B);color:var(--goldb);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-weight:600;font-size:18px;">{{ c.initials }}</span><div style="min-width:0;"><div style="font-size:14.5px;font-weight:600;color:var(--text);">{{ c.name }}</div><div style="font-size:11.5px;color:var(--t2);margin-top:2px;">{{ c.exec }}</div></div></div>
                                    <div style="margin-top:16px;display:flex;justify-content:space-between;align-items:center;"><span :style="c.badge">{{ c.status }}</span><span style="font-family:'Cormorant Garamond',serif;font-size:19px;color:var(--navyt);">{{ c.budget }}</span></div>
                                    <div style="margin-top:14px;padding-top:13px;border-top:1px solid var(--line);font-size:12px;color:var(--t2);"><span style="color:var(--t3);">Interested in </span>{{ c.interest }}</div>
                                    <div style="margin-top:8px;font-size:11.5px;color:var(--t2);"><span style="color:var(--t3);">Referred by </span>{{ c.ref }}</div>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- ============ CLIENT PROFILE ============ -->
                    <template v-if="v.isClientProfile">
                        <button @click="v.backToClients" style="display:flex;align-items:center;gap:5px;margin:18px 0;font:600 12.5px 'Hanken Grotesk',sans-serif;color:var(--t2);background:transparent;border:none;cursor:pointer;"><span style="font-family:'Material Symbols Rounded';font-size:18px;">arrow_back</span>All clients</button>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px;">
                            <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:24px;box-shadow:var(--shadow);">
                                <div style="display:flex;align-items:center;gap:15px;"><span style="width:62px;height:62px;border-radius:50%;background:linear-gradient(135deg,var(--navy),#28406B);color:var(--goldb);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-weight:600;font-size:24px;">{{ v.cp.initials }}</span><div><div style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;color:var(--text);line-height:1;">{{ v.cp.name }}</div><div style="margin-top:8px;"><span :style="v.cp.badge">{{ v.cp.status }}</span></div></div></div>
                                <div style="margin-top:20px;font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--t3);margin-bottom:8px;">Personal Information</div>
                                <div v-for="(c, i) in v.cp.info" :key="i" style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line);font-size:13px;"><span style="color:var(--t2);">{{ c.k }}</span><span style="color:var(--text);font-weight:600;">{{ c.v }}</span></div>
                            </div>
                            <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:24px;box-shadow:var(--shadow);">
                                <div style="font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--t3);margin-bottom:18px;">Activity Timeline</div>
                                <div v-for="(t, i) in v.cp.timeline" :key="i" style="display:flex;gap:13px;"><div style="display:flex;flex-direction:column;align-items:center;"><span style="width:11px;height:11px;border-radius:50%;background:var(--gold);margin-top:3px;"></span><span style="flex:1;width:1.5px;background:var(--line);"></span></div><div style="padding-bottom:18px;"><div style="font-size:13px;font-weight:600;color:var(--text);">{{ t.title }}</div><div style="font-size:11.5px;color:var(--t2);margin-top:2px;">{{ t.date }}</div></div></div>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;margin-top:18px;">
                            <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 24px;box-shadow:var(--shadow);"><div style="font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--t3);margin-bottom:14px;">Payment History</div><div v-for="(p, i) in v.cp.payments" :key="i" style="display:flex;justify-content:space-between;align-items:center;padding:11px 0;border-bottom:1px solid var(--line);"><div><div style="font-size:13px;font-weight:600;color:var(--text);">{{ p.label }}</div><div style="font-size:11px;color:var(--t2);margin-top:2px;">{{ p.date }}</div></div><span style="font-family:'Cormorant Garamond',serif;font-size:17px;color:var(--navyt);">{{ p.amount }}</span></div></div>
                            <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 24px;box-shadow:var(--shadow);"><div style="font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--t3);margin-bottom:14px;">Communication Log</div><div v-for="(m, i) in v.cp.comms" :key="i" style="display:flex;gap:12px;padding:11px 0;border-bottom:1px solid var(--line);"><span style="width:30px;height:30px;flex:none;border-radius:8px;background:var(--golds);color:var(--gold);display:flex;align-items:center;justify-content:center;"><span style="font-family:'Material Symbols Rounded';font-size:16px;">{{ m.icon }}</span></span><div style="min-width:0;"><div style="font-size:13px;font-weight:600;color:var(--text);">{{ m.title }}</div><div style="font-size:11px;color:var(--t2);margin-top:2px;">{{ m.date }}</div></div></div></div>
                        </div>
                    </template>

                    <!-- ============ BUILD ============ -->
                    <template v-if="v.isBuild">
                        <div data-screen-label="Build">
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(330px,1fr));gap:18px;margin-top:20px;">
                                <button v-for="(p, i) in v.buildProjects" :key="i" class="bnk-cardhover" @click="p.onOpen" style="text-align:left;width:100%;background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 24px;box-shadow:var(--shadow);cursor:pointer;">
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;"><div><div style="display:flex;align-items:center;gap:8px;"><span :style="p.dot"></span><span style="font-size:16px;font-weight:600;color:var(--text);">{{ p.name }}</span></div><div style="font-size:11.5px;color:var(--t2);margin-top:5px;display:flex;align-items:center;gap:5px;"><span style="font-family:'Material Symbols Rounded';font-size:14px;color:var(--t3);">engineering</span>{{ p.engineer }}</div></div><span style="font-family:'Cormorant Garamond',serif;font-size:30px;color:var(--navyt);line-height:1;">{{ p.pct }}</span></div>
                                    <div style="height:9px;background:var(--card2);border-radius:5px;overflow:hidden;margin-top:14px;"><div :style="p.bar"></div></div>
                                    <div style="display:flex;justify-content:space-between;margin-top:14px;font-size:12px;"><span style="color:var(--t2);">Budget <span style="color:var(--text);font-weight:600;">{{ p.budget }}</span></span><span style="color:var(--t2);">Spent <span style="color:var(--text);font-weight:600;">{{ p.spent }}</span></span></div>
                                    <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--line);"><div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;"><span style="font-size:10.5px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--t3);">Stages</span><span style="font-size:11px;font-weight:600;color:var(--gold);display:flex;align-items:center;gap:2px;">Live timeline<span style="font-family:'Material Symbols Rounded';font-size:15px;">arrow_forward</span></span></div><div style="display:flex;flex-wrap:wrap;gap:6px;"><span v-for="(stg, j) in p.stages" :key="j" :style="stg.style">{{ stg.name }}</span></div></div>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- ============ BUILD DETAIL ============ -->
                    <template v-if="v.isBuildDetail">
                        <button @click="v.backToBuild" style="display:flex;align-items:center;gap:5px;margin:18px 0;font:600 12.5px 'Hanken Grotesk',sans-serif;color:var(--t2);background:transparent;border:none;cursor:pointer;"><span style="font-family:'Material Symbols Rounded';font-size:18px;">arrow_back</span>All projects</button>
                        <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:24px 26px;box-shadow:var(--shadow);">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                                <div>
                                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;"><div style="font-family:'Cormorant Garamond',serif;font-size:clamp(24px,3.4vw,32px);font-weight:600;color:var(--text);line-height:1;">{{ v.pd.name }}</div><span style="display:inline-flex;align-items:center;gap:6px;font-size:10px;font-weight:700;letter-spacing:.08em;color:var(--alert);background:var(--alerts);padding:4px 10px;border-radius:20px;"><span style="width:7px;height:7px;border-radius:50%;background:var(--alert);animation:softPulse 1.6s ease-in-out infinite;"></span>LIVE</span></div>
                                    <div style="font-size:12.5px;color:var(--t2);margin-top:9px;display:flex;align-items:center;gap:5px;"><span style="font-family:'Material Symbols Rounded';font-size:15px;color:var(--t3);">engineering</span>{{ v.pd.engineer }} · {{ v.pd.timeline }}</div>
                                </div>
                                <div style="text-align:right;"><div style="font-family:'Cormorant Garamond',serif;font-size:46px;line-height:1;color:var(--navyt);">{{ v.pd.pct }}</div><div style="font-size:11.5px;color:var(--t2);margin-top:4px;">{{ v.pd.stageLabel }}</div></div>
                            </div>
                            <div style="height:10px;background:var(--card2);border-radius:6px;overflow:hidden;margin-top:18px;"><div :style="v.pd.bar"></div></div>
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:14px;margin-top:18px;">
                                <div v-for="(x, i) in v.pd.stats" :key="i"><div style="font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--t3);">{{ x.k }}</div><div style="font-family:'Cormorant Garamond',serif;font-size:23px;color:var(--text);margin-top:5px;">{{ x.v }}</div></div>
                            </div>
                        </div>
                        <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:24px 26px;margin-top:18px;box-shadow:var(--shadow);">
                            <div style="font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);margin-bottom:22px;">Live Progress Timeline</div>
                            <div v-for="(st, i) in v.pd.stages" :key="i" style="display:flex;gap:16px;">
                                <div style="display:flex;flex-direction:column;align-items:center;"><span :style="st.dot"></span><span style="flex:1;width:2px;background:var(--line);min-height:16px;"></span></div>
                                <div style="flex:1;min-width:0;padding-bottom:22px;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;"><span style="font-size:14.5px;font-weight:600;color:var(--text);">{{ st.name }}</span><span :style="st.chip">{{ st.statusLabel }}</span></div>
                                    <div style="height:7px;background:var(--card2);border-radius:4px;overflow:hidden;margin-top:11px;"><div :style="st.bar"></div></div>
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:7px;"><span style="font-size:11.5px;color:var(--t2);">{{ st.note }}</span><span style="font-size:11.5px;font-weight:700;color:var(--navyt);">{{ st.pct }}</span></div>
                                    <BnkImageSlot v-if="st.hasPhoto" :id="st.slot" :src="st.photo" placeholder="Site photo" style="width:150px;height:100px;margin-top:12px;border-radius:10px;" />
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- ============ FINANCE ============ -->
                    <template v-if="v.isFinance">
                        <div data-screen-label="Finance">
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-top:20px;">
                                <div v-for="(k, i) in v.financeKpis" :key="i" style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px 22px;box-shadow:var(--shadow);"><div style="font-size:10.5px;font-weight:700;letter-spacing:.13em;text-transform:uppercase;color:var(--t3);">{{ k.label }}</div><div style="font-family:'Cormorant Garamond',serif;font-weight:600;font-size:36px;line-height:1;color:var(--text);margin-top:12px;">{{ k.value }}</div><div style="font-size:11.5px;color:var(--t2);margin-top:8px;">{{ k.note }}</div></div>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px;margin-top:18px;">
                                <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 24px;box-shadow:var(--shadow);">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;"><span style="font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Receivables Ageing</span><span style="font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--navyt);">₦3.1B</span></div>
                                    <div style="display:flex;flex-direction:column;gap:15px;">
                                        <div v-for="(g, i) in v.ageing" :key="i"><div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:6px;"><span style="color:var(--text);font-weight:600;">{{ g.bucket }}</span><span style="display:flex;align-items:center;gap:7px;"><span :style="g.flag">{{ g.note }}</span><span style="font-family:'Cormorant Garamond',serif;font-size:16px;color:var(--navyt);">{{ g.value }}</span></span></div><div style="height:9px;background:var(--card2);border-radius:5px;overflow:hidden;"><div :style="g.bar"></div></div></div>
                                    </div>
                                </div>
                                <div style="background:var(--navy);border:1px solid rgba(168,133,78,.3);border-radius:18px;padding:22px 24px;box-shadow:var(--shadow);display:flex;flex-direction:column;justify-content:center;">
                                    <div style="font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(227,203,151,.7);">Net Cash Position</div>
                                    <div style="font-family:'Cormorant Garamond',serif;font-weight:600;font-size:clamp(42px,6vw,56px);line-height:1;color:#fff;margin-top:12px;">₦1.7B</div>
                                    <div style="font-size:12.5px;color:rgba(244,241,234,.65);margin-top:10px;">₦3.1B receivable less ₦1.4B payable. ₦0.6B of receivables overdue beyond 90 days — FCTA road certificate.</div>
                                </div>
                            </div>
                            <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 24px;margin-top:18px;box-shadow:var(--shadow);">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;"><span style="font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Recent Payments</span><div style="display:flex;gap:10px;"><button style="display:flex;align-items:center;gap:6px;padding:9px 15px;background:var(--navy);color:#fff;border:none;border-radius:10px;font:600 12px 'Hanken Grotesk',sans-serif;cursor:pointer;"><span style="font-family:'Material Symbols Rounded';font-size:16px;">add</span>Record Payment</button><button @click="v.genReceipt" style="display:flex;align-items:center;gap:6px;padding:9px 15px;background:transparent;color:var(--gold);border:1px solid var(--gold);border-radius:10px;font:600 12px 'Hanken Grotesk',sans-serif;cursor:pointer;"><span style="font-family:'Material Symbols Rounded';font-size:16px;">receipt_long</span>Generate Receipt</button></div></div>
                                <div style="overflow-x:auto;">
                                    <div style="min-width:620px;">
                                        <div style="display:grid;grid-template-columns:1.3fr 1.5fr 1fr 1fr 1fr .7fr;gap:12px;padding:0 4px 12px;font-size:10.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--t3);border-bottom:1px solid var(--border);"><span>Payer</span><span>Project</span><span>Amount</span><span>Method</span><span>Date</span><span>Receipt</span></div>
                                        <div v-for="(p, i) in v.payments" :key="i" style="display:grid;grid-template-columns:1.3fr 1.5fr 1fr 1fr 1fr .7fr;gap:12px;padding:14px 4px;border-bottom:1px solid var(--line);align-items:center;font-size:13px;"><span style="font-weight:600;color:var(--text);">{{ p.client }}</span><span style="color:var(--t2);">{{ p.prop }}</span><span style="font-family:'Cormorant Garamond',serif;font-size:16px;color:var(--navyt);">{{ p.amount }}</span><span style="color:var(--t2);">{{ p.method }}</span><span style="color:var(--t2);">{{ p.date }}</span><span style="font-family:'Material Symbols Rounded';font-size:18px;color:var(--gold);cursor:pointer;">download</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- ============ DOCUMENT CENTER ============ -->
                    <template v-if="v.isDocuments">
                        <div data-screen-label="Documents">
                            <div style="display:flex;gap:10px;flex-wrap:wrap;margin:22px 0 20px;">
                                <div v-for="(s2, i) in v.docSteps" :key="i" style="display:flex;align-items:center;gap:9px;padding:10px 16px;background:var(--card);border:1px solid var(--border);border-radius:30px;box-shadow:var(--shadow);"><span style="width:22px;height:22px;border-radius:50%;background:var(--golds);color:var(--gold);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;">{{ s2.n }}</span><span style="font-size:12.5px;font-weight:600;color:var(--text);">{{ s2.label }}</span></div>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
                                <div v-for="(d, i) in v.docCards" :key="i" style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px;box-shadow:var(--shadow);">
                                    <span style="width:44px;height:44px;border-radius:12px;background:var(--golds);color:var(--gold);display:flex;align-items:center;justify-content:center;"><span style="font-family:'Material Symbols Rounded';font-size:22px;">{{ d.icon }}</span></span>
                                    <div style="font-size:15px;font-weight:600;color:var(--text);margin-top:14px;">{{ d.name }}</div>
                                    <div style="font-size:12px;color:var(--t2);margin-top:5px;line-height:1.5;">{{ d.desc }}</div>
                                    <div style="display:flex;gap:8px;margin-top:16px;"><button @click="d.onOpen" style="flex:1;padding:9px;background:var(--navy);color:#fff;border:none;border-radius:9px;font:600 12px 'Hanken Grotesk',sans-serif;cursor:pointer;">Generate</button><button @click="d.onOpen" style="padding:9px 12px;background:transparent;color:var(--t2);border:1px solid var(--border);border-radius:9px;font:600 12px 'Hanken Grotesk',sans-serif;cursor:pointer;">Preview</button></div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- ============ REPORTS ============ -->
                    <template v-if="v.isReports">
                        <div data-screen-label="Reports">
                            <div style="display:flex;align-items:center;gap:3px;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:3px;width:max-content;margin:20px 0 18px;box-shadow:var(--shadow);">
                                <button v-for="(t, i) in v.reportTabs" :key="i" @click="t.onClick" :style="t.style">{{ t.label }}</button>
                            </div>
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;">
                                <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 24px;box-shadow:var(--shadow);">
                                    <div style="font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Revenue</div>
                                    <svg viewBox="0 0 600 170" preserveAspectRatio="none" style="width:100%;height:160px;margin-top:18px;display:block;"><defs><linearGradient id="rp" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#C9A86A" stop-opacity="0.30" /><stop offset="100%" stop-color="#C9A86A" stop-opacity="0" /></linearGradient></defs><path d="M10,120 L90,95 L170,104 L250,70 L330,88 L410,46 L490,64 L580,22 L580,158 L10,158 Z" fill="url(#rp)" /><path d="M10,120 L90,95 L170,104 L250,70 L330,88 L410,46 L490,64 L580,22" fill="none" style="stroke:var(--gold);" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                </div>
                                <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 24px;box-shadow:var(--shadow);">
                                    <div style="font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Construction Cost</div>
                                    <div style="display:flex;align-items:flex-end;gap:12px;height:150px;margin-top:22px;">
                                        <div v-for="(b, i) in v.costBars" :key="i" style="flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;height:100%;justify-content:flex-end;"><div :style="b.style"></div><span style="font-size:10px;color:var(--t3);">{{ b.label }}</span></div>
                                    </div>
                                </div>
                                <div style="background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px 24px;box-shadow:var(--shadow);">
                                    <div style="font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--t3);">Order Book by Sector</div>
                                    <div style="margin-top:18px;display:flex;flex-direction:column;gap:15px;">
                                        <div v-for="(b, i) in v.salesBars" :key="i"><div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:6px;"><span style="color:var(--text);font-weight:600;">{{ b.name }}</span><span style="color:var(--navyt);font-family:'Cormorant Garamond',serif;font-size:16px;">{{ b.value }}</span></div><div style="height:9px;background:var(--card2);border-radius:5px;overflow:hidden;"><div :style="b.bar"></div></div></div>
                                    </div>
                                </div>
                                <div style="background:var(--navy);border:1px solid rgba(168,133,78,.3);border-radius:18px;padding:22px 24px;box-shadow:var(--shadow);display:flex;flex-direction:column;justify-content:center;">
                                    <div style="font-size:10.5px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(227,203,151,.7);">Net Margin · Portfolio</div>
                                    <div style="font-family:'Cormorant Garamond',serif;font-weight:600;font-size:clamp(46px,7vw,64px);line-height:1;color:#fff;margin-top:12px;">₦3.0B</div>
                                    <div style="font-size:12.5px;color:rgba(244,241,234,.65);margin-top:10px;">21% blended margin across construction, estates &amp; energy</div>
                                </div>
                            </div>
                        </div>
                    </template>
                </main>

                <!-- MOBILE BOTTOM NAV -->
                <nav id="bottomnav" style="position:fixed;bottom:0;left:0;right:0;z-index:50;align-items:center;justify-content:space-around;padding:8px 6px calc(8px + env(safe-area-inset-bottom));background:var(--card);border-top:1px solid var(--border);box-shadow:0 -6px 24px rgba(0,0,0,.08);">
                    <button v-for="(n, i) in v.mobileNav" :key="i" @click="n.onClick" :style="n.style"><span class="nav-ic" style="font-size:23px;">{{ n.icon }}</span><span style="font-size:9.5px;font-weight:600;">{{ n.label }}</span></button>
                </nav>

                <!-- MOBILE MORE SHEET -->
                <div v-if="v.moreOpen" @click="v.closeMore" style="position:fixed;inset:0;z-index:60;background:rgba(10,11,13,.5);display:flex;align-items:flex-end;">
                    <div style="width:100%;background:var(--card);border-radius:20px 20px 0 0;padding:14px 16px calc(18px + env(safe-area-inset-bottom));border-top:1px solid var(--border);box-shadow:0 -10px 40px rgba(0,0,0,.3);">
                        <div style="width:42px;height:4px;border-radius:3px;background:var(--border);margin:2px auto 16px;"></div>
                        <button v-for="(m, i) in v.moreItems" :key="i" @click="m.onClick" :style="m.style"><span class="nav-ic" style="font-size:23px;">{{ m.icon }}</span><span style="font-size:14.5px;font-weight:600;">{{ m.label }}</span></button>
                    </div>
                </div>

                <!-- PROJECT DETAIL DRAWER -->
                <div v-if="v.drawerOpen" @click="v.closeDrawer" style="position:fixed;inset:0;z-index:150;background:rgba(10,11,13,.5);display:flex;justify-content:flex-end;">
                    <div id="drawer-panel" @click="v.stop" style="width:100%;max-width:480px;height:100%;background:var(--card);border-left:1px solid var(--border);box-shadow:-20px 0 60px rgba(0,0,0,.3);overflow-y:auto;animation:drawerIn .32s cubic-bezier(.2,.7,.2,1);">
                        <div style="position:relative;background:var(--navy);padding:24px 26px 22px;">
                            <button @click="v.closeDrawer" style="position:absolute;top:18px;right:18px;width:34px;height:34px;border-radius:9px;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.06);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;"><span style="font-family:'Material Symbols Rounded';font-size:18px;">close</span></button>
                            <span :style="v.dr.tagStyleLight">{{ v.dr.tag }}</span>
                            <div style="font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:600;color:#fff;line-height:1.1;margin-top:12px;max-width:340px;">{{ v.dr.name }}</div>
                            <div style="font-size:12.5px;color:rgba(244,241,234,.7);margin-top:7px;">{{ v.dr.location }} · {{ v.dr.client }}</div>
                            <div style="display:flex;align-items:flex-end;gap:14px;margin-top:18px;"><div style="font-family:'Cormorant Garamond',serif;font-size:40px;color:#E3CB97;line-height:1;">{{ v.dr.pct }}</div><span :style="v.dr.healthBadge + 'margin-bottom:6px;'">{{ v.dr.healthLabel }}</span></div>
                            <div style="height:8px;background:rgba(255,255,255,.12);border-radius:5px;overflow:hidden;margin-top:12px;"><div :style="v.dr.bar"></div></div>
                        </div>
                        <div style="padding:22px 26px;">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                                <div v-for="(x, i) in v.dr.stats" :key="i" style="background:var(--card2);border:1px solid var(--border);border-radius:13px;padding:14px 16px;"><div style="font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--t3);">{{ x.k }}</div><div style="font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--text);margin-top:5px;">{{ x.v }}</div></div>
                            </div>

                            <template v-if="v.dr.isEstate">
                                <div style="font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--t3);margin:24px 0 12px;">Units</div>
                                <div style="display:flex;height:18px;border-radius:6px;overflow:hidden;margin-bottom:14px;">
                                    <div v-for="(u, i) in v.dr.units" :key="i" :style="u.seg"></div>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:10px;">
                                    <div v-for="(u, i) in v.dr.units" :key="i" style="display:flex;align-items:center;justify-content:space-between;"><div style="display:flex;align-items:center;gap:9px;"><span :style="u.dot"></span><span style="font-size:12.5px;font-weight:600;color:var(--text);">{{ u.label }}</span></div><span style="font-size:12px;color:var(--t2);">{{ u.units }} units · <span style="font-family:'Cormorant Garamond',serif;font-size:15px;color:var(--navyt);">{{ u.value }}</span></span></div>
                                </div>
                            </template>

                            <div style="font-size:10.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--t3);margin:24px 0 14px;">Milestone Tracker</div>
                            <div v-for="(m, i) in v.dr.milestones" :key="i" style="display:flex;gap:13px;"><div style="display:flex;flex-direction:column;align-items:center;"><span :style="m.dot"></span><span style="flex:1;width:2px;background:var(--line);min-height:14px;"></span></div><div style="padding-bottom:14px;flex:1;"><div style="display:flex;align-items:center;justify-content:space-between;"><span style="font-size:13px;font-weight:600;color:var(--text);">{{ m.name }}</span><span :style="m.chip">{{ m.status }}</span></div></div></div>

                            <button @click="v.dr.onTimeline" style="width:100%;margin-top:8px;padding:13px;background:var(--navy);color:#fff;border:none;border-radius:12px;font:600 13px 'Hanken Grotesk',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;"><span style="font-family:'Material Symbols Rounded';font-size:18px;">timeline</span>Open full build timeline</button>
                        </div>
                    </div>
                </div>

                <!-- DOCUMENT PREVIEW MODAL -->
                <div v-if="v.docOpen" id="doc-modal" style="position:fixed;inset:0;z-index:200;background:rgba(10,11,13,.62);display:flex;flex-direction:column;">
                    <div id="doc-toolbar" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 18px;background:var(--card);border-bottom:1px solid var(--border);">
                        <div style="display:flex;align-items:center;gap:10px;min-width:0;"><span style="font-family:'Material Symbols Rounded';font-size:20px;color:var(--gold);">draft</span><span style="font-size:14px;font-weight:600;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ v.doc.title }} · Preview</span></div>
                        <div style="display:flex;gap:10px;flex:none;">
                            <button @click="v.printDoc" style="display:flex;align-items:center;gap:6px;padding:10px 16px;background:var(--navy);color:#fff;border:none;border-radius:10px;font:600 12px 'Hanken Grotesk',sans-serif;cursor:pointer;"><span style="font-family:'Material Symbols Rounded';font-size:16px;">download</span>Download PDF</button>
                            <button @click="v.closeDoc" style="width:40px;height:40px;border-radius:10px;border:1px solid var(--border);background:var(--card2);color:var(--t2);cursor:pointer;display:flex;align-items:center;justify-content:center;"><span style="font-family:'Material Symbols Rounded';font-size:19px;">close</span></button>
                        </div>
                    </div>
                    <div id="doc-scroll" style="flex:1;overflow:auto;padding:26px 16px 60px;display:flex;justify-content:center;align-items:flex-start;">
                        <!-- ===== PAYMENT RECEIPT ===== -->
                        <div v-if="v.doc.isReceipt" id="doc-paper" class="bnk-rc" style="position:relative;width:100%;max-width:794px;background:#fff;color:#23262B;border-radius:6px;overflow:hidden;box-shadow:0 30px 70px rgba(0,0,0,.45);">
                            <div style="height:4px;background:linear-gradient(90deg,#1C2E4A 0%,#1C2E4A 42%,#A8854E 42%,#C9A86A 72%,#A8854E 100%);"></div>
                            <div class="rc-x" style="padding:46px 56px 28px;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                                <div style="display:flex;align-items:center;"><img :src="LOGO_LIGHT" alt="Bankrol — Construction, Investment, Energy" style="height:44px;width:auto;display:block;" /></div>
                                <div style="text-align:right;">
                                    <div style="font-family:'Poppins',sans-serif;font-weight:600;font-size:30px;line-height:.95;letter-spacing:.01em;color:rgba(35,38,43,.22);">Receipt</div>
                                    <div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;margin-top:8px;letter-spacing:.04em;">No. {{ v.rcpt.receiptNo }}</div>
                                </div>
                            </div>
                            <div class="rc-hero" style="margin:0 56px;padding:30px 34px;border:1px solid rgba(94,115,85,.28);background:rgba(94,115,85,.06);border-radius:14px;position:relative;overflow:hidden;">
                                <div style="position:absolute;top:24px;right:30px;transform:rotate(-9deg);border:2px solid rgba(94,115,85,.5);color:#5E7355;border-radius:8px;padding:6px 16px;font-family:'IBM Plex Mono',monospace;font-weight:600;font-size:20px;letter-spacing:.22em;opacity:.9;">PAID</div>
                                <div style="display:flex;align-items:center;gap:9px;color:#5E7355;"><span style="font-family:'Material Symbols Rounded';font-size:20px;">check_circle</span><span style="font-size:11px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;">Payment received in full</span></div>
                                <div class="rc-amt" style="font-family:'IBM Plex Mono',monospace;font-size:46px;font-weight:600;color:#1C2E4A;margin-top:16px;letter-spacing:-.01em;overflow-wrap:anywhere;">{{ v.rcpt.amountPaid }}</div>
                                <div style="font-size:13.5px;color:rgba(35,38,43,.6);margin-top:8px;max-width:520px;line-height:1.5;">{{ v.rcpt.paidFor }}</div>
                            </div>
                            <div class="rc-x rc-grid" style="padding:30px 56px 0;display:grid;grid-template-columns:1fr 1fr;gap:26px 40px;">
                                <div>
                                    <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;">Received from</div>
                                    <div style="font-size:15px;font-weight:600;color:#23262B;margin-top:8px;">{{ v.rcpt.payer }}</div>
                                    <div style="font-size:12.5px;color:rgba(35,38,43,.6);line-height:1.55;margin-top:3px;">{{ v.rcpt.payerLine }}</div>
                                </div>
                                <div>
                                    <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;">Date paid</div>
                                    <div style="font-family:'IBM Plex Mono',monospace;font-size:14px;color:#23262B;margin-top:9px;">{{ v.rcpt.datePaid }}</div>
                                </div>
                                <div>
                                    <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;">Payment method</div>
                                    <div style="font-size:13.5px;color:#23262B;margin-top:9px;">{{ v.rcpt.payMethod }}</div>
                                </div>
                                <div>
                                    <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;">Transaction ID</div>
                                    <div style="font-family:'IBM Plex Mono',monospace;font-size:13.5px;color:#23262B;margin-top:9px;">{{ v.rcpt.txnId }}</div>
                                </div>
                            </div>
                            <div class="rc-hx" style="height:1px;background:#ECE8E0;margin:30px 56px 0;"></div>
                            <div class="rc-x" style="padding:24px 56px 0;">
                                <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;margin-bottom:6px;">Applied to</div>
                                <div v-for="(row, i) in v.rcpt.items" :key="i" style="display:flex;align-items:baseline;justify-content:space-between;padding:13px 0;border-bottom:1px solid #F4F1EA;">
                                    <div style="font-size:14px;color:#23262B;">{{ row.title }}</div>
                                    <div style="font-family:'IBM Plex Mono',monospace;font-size:13.5px;color:#23262B;">{{ row.amount }}</div>
                                </div>
                                <div style="display:flex;justify-content:flex-end;margin-top:18px;">
                                    <div style="width:300px;max-width:100%;">
                                        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px;color:rgba(35,38,43,.6);"><span>Invoice total</span><span style="font-family:'IBM Plex Mono',monospace;color:#23262B;">{{ v.rcpt.invoiceTotal }}</span></div>
                                        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px;color:#5E7355;"><span>Amount paid</span><span style="font-family:'IBM Plex Mono',monospace;">−{{ v.rcpt.amountPaid }}</span></div>
                                        <div style="height:1px;background:#ECE8E0;margin:9px 0;"></div>
                                        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 14px;margin:0 -14px;background:rgba(168,133,78,.08);border-radius:9px;"><span style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:#1C2E4A;">Balance remaining</span><span style="font-family:'IBM Plex Mono',monospace;font-size:18px;font-weight:600;color:#1C2E4A;">{{ v.rcpt.balance }}</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="rc-x rc-grid" style="padding:34px 56px 0;display:grid;grid-template-columns:1.5fr 1fr;gap:40px;align-items:end;">
                                <div>
                                    <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;">Confirmation</div>
                                    <div style="font-size:12.5px;color:rgba(35,38,43,.62);line-height:1.6;margin-top:9px;">{{ v.rcpt.note }}</div>
                                </div>
                                <div style="text-align:center;">
                                    <div style="font-family:'Cormorant Garamond',serif;font-size:26px;font-style:italic;color:#1C2E4A;border-bottom:1px solid #ECE8E0;padding-bottom:10px;">{{ v.rcpt.signature }}</div>
                                    <div style="font-size:11px;color:#9A958B;letter-spacing:.05em;margin-top:9px;">{{ v.rcpt.signatureRole }}</div>
                                </div>
                            </div>
                            <div class="rc-x" style="margin-top:38px;padding:22px 56px;background:#1C2E4A;color:rgba(244,241,234,.72);display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;font-size:11.5px;letter-spacing:.03em;">
                                <span>{{ v.rcpt.footerLeft }}</span>
                                <span style="font-family:'IBM Plex Mono',monospace;color:#C9A86A;">{{ v.rcpt.footerRight }}</span>
                            </div>
                        </div>

                        <!-- ===== INVOICE ===== -->
                        <div v-else-if="v.doc.isInvoice" id="doc-paper" class="bnk-rc" style="position:relative;width:100%;max-width:794px;background:#fff;color:#23262B;border-radius:6px;overflow:hidden;box-shadow:0 30px 70px rgba(0,0,0,.45);">
                            <div style="height:4px;background:linear-gradient(90deg,#1C2E4A 0%,#1C2E4A 42%,#A8854E 42%,#C9A86A 72%,#A8854E 100%);"></div>
                            <div class="rc-x" style="padding:46px 56px 30px;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                                <div style="display:flex;align-items:center;"><img :src="LOGO_LIGHT" alt="Bankrol — Construction, Investment, Energy" style="height:44px;width:auto;display:block;" /></div>
                                <div style="text-align:right;">
                                    <div style="font-family:'Poppins',sans-serif;font-weight:600;font-size:30px;line-height:.95;letter-spacing:.01em;color:rgba(35,38,43,.22);">Invoice</div>
                                    <div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;margin-top:8px;letter-spacing:.04em;">{{ v.inv.invoiceNo }}</div>
                                </div>
                            </div>
                            <div class="rc-hx" style="height:1px;background:#ECE8E0;margin:0 56px;"></div>
                            <div class="rc-x rc-grid" style="padding:26px 56px 0;display:grid;grid-template-columns:1.3fr 1fr 1fr;gap:24px;">
                                <div>
                                    <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;">Billed to</div>
                                    <div style="font-size:15px;font-weight:600;color:#23262B;margin-top:9px;">{{ v.inv.clientName }}</div>
                                    <div style="font-size:12.5px;color:rgba(35,38,43,.6);line-height:1.55;margin-top:4px;">{{ v.inv.clientLine1 }}<br />{{ v.inv.clientLine2 }}</div>
                                </div>
                                <div>
                                    <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;">Date issued</div>
                                    <div style="font-family:'IBM Plex Mono',monospace;font-size:13.5px;color:#23262B;margin-top:9px;">{{ v.inv.dateIssued }}</div>
                                    <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;margin-top:18px;">Payment to</div>
                                    <div style="font-size:13px;color:#23262B;margin-top:7px;">{{ v.inv.payMethod }}</div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;">Status</div>
                                    <div style="display:inline-flex;align-items:center;gap:7px;margin-top:9px;padding:7px 13px;border-radius:999px;background:rgba(194,138,46,.13);color:#B07C1E;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;border:1px solid rgba(194,138,46,.32);"><span style="font-family:'Material Symbols Rounded';font-size:15px;">schedule</span>Due</div>
                                    <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;margin-top:18px;">Reference</div>
                                    <div style="font-family:'IBM Plex Mono',monospace;font-size:13px;color:#23262B;margin-top:7px;">{{ v.inv.reference }}</div>
                                </div>
                            </div>
                            <div class="rc-x" style="padding:32px 56px 0;">
                                <div class="inv-grid" style="display:grid;grid-template-columns:1fr 78px 130px 140px;gap:14px;padding:0 0 11px;border-bottom:1px solid #ECE8E0;">
                                    <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;">Description</div>
                                    <div class="inv-sec" style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;text-align:right;">Qty</div>
                                    <div class="inv-sec" style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;text-align:right;">Unit</div>
                                    <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;text-align:right;">Amount</div>
                                </div>
                                <div v-for="(row, i) in v.inv.items" :key="i" class="inv-grid" style="display:grid;grid-template-columns:1fr 78px 130px 140px;gap:14px;padding:17px 0;border-bottom:1px solid #F4F1EA;align-items:baseline;">
                                    <div>
                                        <div style="font-size:14px;font-weight:600;color:#23262B;">{{ row.title }}</div>
                                        <div style="font-size:12px;color:rgba(35,38,43,.55);margin-top:3px;line-height:1.45;">{{ row.detail }}</div>
                                    </div>
                                    <div class="inv-sec" style="font-family:'IBM Plex Mono',monospace;font-size:13px;color:rgba(35,38,43,.7);text-align:right;">{{ row.qty }}</div>
                                    <div class="inv-sec" style="font-family:'IBM Plex Mono',monospace;font-size:13px;color:rgba(35,38,43,.7);text-align:right;">{{ row.unit }}</div>
                                    <div style="font-family:'IBM Plex Mono',monospace;font-size:13.5px;font-weight:500;color:#23262B;text-align:right;">{{ row.amount }}</div>
                                </div>
                            </div>
                            <div class="rc-x" style="padding:22px 56px 0;display:flex;justify-content:flex-end;">
                                <div style="width:330px;max-width:100%;">
                                    <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;color:rgba(35,38,43,.6);"><span>Subtotal</span><span style="font-family:'IBM Plex Mono',monospace;color:#23262B;">{{ v.inv.subtotal }}</span></div>
                                    <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13px;color:rgba(35,38,43,.6);"><span>{{ v.inv.taxLabel }}</span><span style="font-family:'IBM Plex Mono',monospace;color:#23262B;">{{ v.inv.tax }}</span></div>
                                    <div style="height:1px;background:#ECE8E0;margin:11px 0;"></div>
                                    <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 14px;margin:0 -14px;background:rgba(168,133,78,.08);border-radius:9px;"><span style="font-family:'Cormorant Garamond',serif;font-size:19px;font-weight:600;color:#1C2E4A;">Total due</span><span style="font-family:'IBM Plex Mono',monospace;font-size:21px;font-weight:600;color:#1C2E4A;">{{ v.inv.total }}</span></div>
                                </div>
                            </div>
                            <div class="rc-x rc-grid" style="padding:38px 56px 0;display:grid;grid-template-columns:1.5fr 1fr;gap:40px;align-items:end;">
                                <div>
                                    <div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;">Notes</div>
                                    <div style="font-size:12.5px;color:rgba(35,38,43,.62);line-height:1.6;margin-top:9px;">{{ v.inv.notes }}</div>
                                </div>
                                <div style="text-align:center;">
                                    <div style="font-family:'Cormorant Garamond',serif;font-size:26px;font-style:italic;color:#1C2E4A;border-bottom:1px solid #ECE8E0;padding-bottom:10px;">{{ v.inv.signature }}</div>
                                    <div style="font-size:11px;color:#9A958B;letter-spacing:.05em;margin-top:9px;">{{ v.inv.signatureRole }}</div>
                                </div>
                            </div>
                            <div class="rc-x" style="margin-top:42px;padding:22px 56px;background:#1C2E4A;color:rgba(244,241,234,.72);display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;font-size:11.5px;letter-spacing:.03em;">
                                <span>{{ v.inv.footerLeft }}</span>
                                <span style="font-family:'IBM Plex Mono',monospace;color:#C9A86A;">{{ v.inv.footerRight }}</span>
                            </div>
                        </div>

                        <!-- ===== SALE & PURCHASE AGREEMENT (3 pages) ===== -->
                        <div v-else-if="v.doc.isAgreement" id="doc-paper" class="bnk-agr" style="width:100%;max-width:794px;background:transparent;box-shadow:none;display:flex;flex-direction:column;gap:22px;">

                            <!-- PAGE 1 — COVER -->
                            <div class="bnk-page" style="position:relative;min-height:1123px;background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 30px 70px rgba(0,0,0,.45);display:flex;flex-direction:column;">
                                <div style="height:4px;background:linear-gradient(90deg,#1C2E4A 0%,#1C2E4A 42%,#A8854E 42%,#C9A86A 72%,#A8854E 100%);"></div>
                                <div v-if="v.agr.showWatermark" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;z-index:0;"><div style="font-family:'IBM Plex Mono',monospace;font-weight:600;font-size:150px;letter-spacing:.18em;color:rgba(28,46,74,.05);transform:rotate(-28deg);">DRAFT</div></div>
                                <div style="position:relative;z-index:1;padding:64px 64px 0;flex:1;display:flex;flex-direction:column;">
                                    <div style="display:flex;align-items:center;"><img :src="LOGO_LIGHT" alt="Bankrol — Construction, Investment, Energy" style="height:46px;width:auto;display:block;" /></div>
                                    <div style="margin-top:88px;">
                                        <div style="font-size:11px;font-weight:600;letter-spacing:.24em;text-transform:uppercase;color:#A8854E;">{{ v.agr.instrument }}</div>
                                        <div style="font-family:'Cormorant Garamond',serif;font-weight:600;font-size:62px;line-height:1.02;color:#1C2E4A;letter-spacing:.005em;margin-top:14px;">Sale &amp; Purchase<br />Agreement</div>
                                        <div style="font-size:15px;color:rgba(35,38,43,.62);line-height:1.6;margin-top:22px;max-width:560px;">Made in respect of the property described in the Schedule, by and between the parties named below, on the terms and conditions hereinafter set out.</div>
                                    </div>
                                    <div style="margin-top:46px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:24px;padding:24px 0;border-top:1px solid #ECE8E0;border-bottom:1px solid #ECE8E0;">
                                        <div><div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;">Agreement no.</div><div style="font-family:'IBM Plex Mono',monospace;font-size:14px;color:#23262B;margin-top:8px;">{{ v.agr.agreementNo }}</div></div>
                                        <div><div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;">Date of agreement</div><div style="font-family:'IBM Plex Mono',monospace;font-size:14px;color:#23262B;margin-top:8px;">{{ v.agr.agreementDate }}</div></div>
                                        <div><div style="font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#9A958B;">Consideration</div><div style="font-family:'IBM Plex Mono',monospace;font-size:14px;color:#1C2E4A;font-weight:600;margin-top:8px;">{{ v.agr.totalPrice }}</div></div>
                                    </div>
                                    <div style="margin-top:40px;display:grid;grid-template-columns:1fr 1fr;gap:30px;">
                                        <div style="padding:24px 26px;border:1px solid #ECE8E0;border-radius:12px;background:#FBFAF6;"><div style="font-size:10px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:#A8854E;">The Vendor</div><div style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;color:#1C2E4A;margin-top:12px;">{{ v.agr.vendorName }}</div><div style="font-size:12.5px;color:rgba(35,38,43,.6);line-height:1.6;margin-top:8px;">{{ v.agr.vendorLine1 }}<br />{{ v.agr.vendorLine2 }}</div><div style="font-size:11px;color:#9A958B;letter-spacing:.04em;margin-top:12px;">{{ v.agr.vendorMeta }}</div></div>
                                        <div style="padding:24px 26px;border:1px solid #ECE8E0;border-radius:12px;background:#FBFAF6;"><div style="font-size:10px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:#A8854E;">The Purchaser</div><div style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;color:#1C2E4A;margin-top:12px;">{{ v.agr.purchaserName }}</div><div style="font-size:12.5px;color:rgba(35,38,43,.6);line-height:1.6;margin-top:8px;">{{ v.agr.purchaserLine1 }}<br />{{ v.agr.purchaserLine2 }}</div><div style="font-size:11px;color:#9A958B;letter-spacing:.04em;margin-top:12px;">{{ v.agr.purchaserMeta }}</div></div>
                                    </div>
                                    <div style="margin-top:34px;">
                                        <div style="font-size:10px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:#9A958B;">Recitals</div>
                                        <div style="display:flex;gap:14px;margin-top:14px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#A8854E;min-width:24px;">A</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">The Vendor is the registered owner of and is lawfully seised of the property described in the Schedule (the <b style="color:#23262B;">&ldquo;Property&rdquo;</b>), free from material encumbrance save as disclosed in writing to the Purchaser.</div></div>
                                        <div style="display:flex;gap:14px;margin-top:12px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#A8854E;min-width:24px;">B</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">The Vendor has agreed to sell and the Purchaser has agreed to purchase the Property for the consideration and upon the terms set out in this Agreement.</div></div>
                                    </div>
                                </div>
                                <div style="position:relative;z-index:1;margin-top:30px;padding:18px 64px;display:flex;justify-content:space-between;align-items:center;border-top:1px solid #ECE8E0;font-size:10px;color:#9A958B;letter-spacing:.04em;"><span>Bankrol Limited · RC 1029384</span><span style="font-family:'IBM Plex Mono',monospace;letter-spacing:.1em;">PAGE 01 / 03</span><span>Purchaser initials&nbsp;&nbsp;__________</span></div>
                            </div>

                            <!-- PAGE 2 — TERMS -->
                            <div class="bnk-page" style="position:relative;min-height:1123px;background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 30px 70px rgba(0,0,0,.45);display:flex;flex-direction:column;">
                                <div style="height:4px;background:linear-gradient(90deg,#1C2E4A 0%,#1C2E4A 42%,#A8854E 42%,#C9A86A 72%,#A8854E 100%);"></div>
                                <div style="padding:40px 64px 0;flex:1;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:16px;border-bottom:1px solid #ECE8E0;"><div style="display:flex;align-items:center;gap:10px;"><div style="width:9px;height:9px;background:linear-gradient(135deg,#C9A86A,#A8854E);transform:rotate(45deg);border-radius:1px;"></div><span style="font-family:'Cormorant Garamond',serif;letter-spacing:.26em;font-size:15px;font-weight:600;color:#1C2E4A;">{{ v.agr.brand }}</span></div><span style="font-family:'IBM Plex Mono',monospace;font-size:10px;letter-spacing:.1em;color:#9A958B;">SALE &amp; PURCHASE · {{ v.agr.agreementNo }}</span></div>
                                    <div style="margin-top:30px;">
                                        <div style="display:flex;gap:16px;align-items:baseline;"><div style="font-family:'IBM Plex Mono',monospace;font-size:14px;font-weight:600;color:#A8854E;min-width:26px;">01</div><div style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;color:#1C2E4A;">Sale of the Property</div></div>
                                        <div style="padding-left:42px;margin-top:8px;">
                                            <div style="display:flex;gap:12px;margin-top:10px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;min-width:32px;">1.1</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">The Vendor shall sell and the Purchaser shall purchase the Property, together with all fixtures, fittings and appurtenant rights, free from encumbrances on completion.</div></div>
                                            <div style="display:flex;gap:12px;margin-top:10px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;min-width:32px;">1.2</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">Title shall pass to the Purchaser upon completion and receipt by the Vendor of the full purchase consideration in cleared funds.</div></div>
                                        </div>
                                    </div>
                                    <div style="margin-top:26px;">
                                        <div style="display:flex;gap:16px;align-items:baseline;"><div style="font-family:'IBM Plex Mono',monospace;font-size:14px;font-weight:600;color:#A8854E;min-width:26px;">02</div><div style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;color:#1C2E4A;">Purchase Price &amp; Payment</div></div>
                                        <div style="padding-left:42px;margin-top:8px;">
                                            <div style="display:flex;gap:12px;margin-top:10px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;min-width:32px;">2.1</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">The total consideration for the Property is <b style="color:#1C2E4A;">{{ v.agr.totalPrice }}</b> ({{ v.agr.totalWords }}), payable in accordance with the schedule below.</div></div>
                                            <div style="display:flex;gap:12px;margin-top:10px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;min-width:32px;">2.2</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">Time shall be of the essence in respect of each instalment. Any sum not paid by its due date shall attract interest at {{ v.agr.interestRate }} per month until discharged.</div></div>
                                            <div style="margin-top:18px;border:1px solid #ECE8E0;border-radius:10px;overflow:hidden;">
                                                <div style="display:grid;grid-template-columns:34px 1fr 150px 96px;gap:12px;padding:11px 16px;background:#FBFAF6;border-bottom:1px solid #ECE8E0;"><div style="font-size:9.5px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#9A958B;">#</div><div style="font-size:9.5px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#9A958B;">Milestone</div><div style="font-size:9.5px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#9A958B;text-align:right;">Amount</div><div style="font-size:9.5px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#9A958B;text-align:right;">Status</div></div>
                                                <div v-for="(row, i) in v.agr.schedule" :key="i" style="display:grid;grid-template-columns:34px 1fr 150px 96px;gap:12px;padding:13px 16px;border-bottom:1px solid #F4F1EA;align-items:center;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#A8854E;">{{ row.n }}</div><div><div style="font-size:13px;font-weight:600;color:#23262B;">{{ row.title }}</div><div style="font-size:11.5px;color:rgba(35,38,43,.55);margin-top:2px;">{{ row.due }}</div></div><div style="font-family:'IBM Plex Mono',monospace;font-size:13px;color:#23262B;text-align:right;">{{ row.amount }}</div><div style="text-align:right;"><span :style="'font-size:10px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:' + row.statusFg + ';'">{{ row.status }}</span></div></div>
                                                <div style="display:grid;grid-template-columns:34px 1fr 150px 96px;gap:12px;padding:13px 16px;background:rgba(168,133,78,.07);align-items:center;"><div></div><div style="font-family:'Cormorant Garamond',serif;font-size:17px;font-weight:600;color:#1C2E4A;">Total consideration</div><div style="font-family:'IBM Plex Mono',monospace;font-size:14px;font-weight:600;color:#1C2E4A;text-align:right;">{{ v.agr.totalPrice }}</div><div></div></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="margin-top:26px;">
                                        <div style="display:flex;gap:16px;align-items:baseline;"><div style="font-family:'IBM Plex Mono',monospace;font-size:14px;font-weight:600;color:#A8854E;min-width:26px;">03</div><div style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;color:#1C2E4A;">Completion</div></div>
                                        <div style="padding-left:42px;margin-top:8px;">
                                            <div style="display:flex;gap:12px;margin-top:10px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;min-width:32px;">3.1</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">Completion shall take place on or before <b style="color:#23262B;">{{ v.agr.completionDate }}</b>, upon which the Vendor shall deliver vacant possession and execute all documents necessary to vest title in the Purchaser.</div></div>
                                            <div style="display:flex;gap:12px;margin-top:10px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;min-width:32px;">3.2</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">The Vendor shall obtain, at its cost, the Governor&rsquo;s consent to the assignment and shall procure registration of the Deed of Assignment in favour of the Purchaser.</div></div>
                                        </div>
                                    </div>
                                </div>
                                <div style="margin-top:24px;padding:18px 64px;display:flex;justify-content:space-between;align-items:center;border-top:1px solid #ECE8E0;font-size:10px;color:#9A958B;letter-spacing:.04em;"><span>Bankrol Limited · RC 1029384</span><span style="font-family:'IBM Plex Mono',monospace;letter-spacing:.1em;">PAGE 02 / 03</span><span>Purchaser initials&nbsp;&nbsp;__________</span></div>
                            </div>

                            <!-- PAGE 3 — WARRANTIES + EXECUTION -->
                            <div class="bnk-page" style="position:relative;min-height:1123px;background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 30px 70px rgba(0,0,0,.45);display:flex;flex-direction:column;">
                                <div style="height:4px;background:linear-gradient(90deg,#1C2E4A 0%,#1C2E4A 42%,#A8854E 42%,#C9A86A 72%,#A8854E 100%);"></div>
                                <div style="padding:40px 64px 0;flex:1;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:16px;border-bottom:1px solid #ECE8E0;"><div style="display:flex;align-items:center;gap:10px;"><div style="width:9px;height:9px;background:linear-gradient(135deg,#C9A86A,#A8854E);transform:rotate(45deg);border-radius:1px;"></div><span style="font-family:'Cormorant Garamond',serif;letter-spacing:.26em;font-size:15px;font-weight:600;color:#1C2E4A;">{{ v.agr.brand }}</span></div><span style="font-family:'IBM Plex Mono',monospace;font-size:10px;letter-spacing:.1em;color:#9A958B;">SALE &amp; PURCHASE · {{ v.agr.agreementNo }}</span></div>
                                    <div style="margin-top:28px;">
                                        <div style="display:flex;gap:16px;align-items:baseline;"><div style="font-family:'IBM Plex Mono',monospace;font-size:14px;font-weight:600;color:#A8854E;min-width:26px;">04</div><div style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;color:#1C2E4A;">Warranties &amp; Representations</div></div>
                                        <div style="padding-left:42px;margin-top:8px;">
                                            <div style="display:flex;gap:12px;margin-top:10px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;min-width:32px;">4.1</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">The Vendor warrants that it has good and marketable title to the Property and full power to sell the same, and that there are no subsisting disputes, charges or adverse claims affecting it.</div></div>
                                            <div style="display:flex;gap:12px;margin-top:10px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;min-width:32px;">4.2</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">The Purchaser warrants that it has inspected the Property and enters into this Agreement on the basis of its own due diligence, save for the warranties expressly given herein.</div></div>
                                        </div>
                                    </div>
                                    <div style="margin-top:24px;">
                                        <div style="display:flex;gap:16px;align-items:baseline;"><div style="font-family:'IBM Plex Mono',monospace;font-size:14px;font-weight:600;color:#A8854E;min-width:26px;">05</div><div style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;color:#1C2E4A;">Default &amp; Termination</div></div>
                                        <div style="padding-left:42px;margin-top:8px;">
                                            <div style="display:flex;gap:12px;margin-top:10px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;min-width:32px;">5.1</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">If the Purchaser fails to pay any instalment within {{ v.agr.cureDays }} of its due date, the Vendor may, by written notice, terminate this Agreement and forfeit a sum equal to {{ v.agr.forfeit }} of the price paid as agreed liquidated damages.</div></div>
                                            <div style="display:flex;gap:12px;margin-top:10px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;min-width:32px;">5.2</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">If the Vendor fails to complete in accordance with Clause 3, the Purchaser may rescind and recover all sums paid, together with interest at the rate stated in Clause 2.2.</div></div>
                                        </div>
                                    </div>
                                    <div style="margin-top:24px;">
                                        <div style="display:flex;gap:16px;align-items:baseline;"><div style="font-family:'IBM Plex Mono',monospace;font-size:14px;font-weight:600;color:#A8854E;min-width:26px;">06</div><div style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;color:#1C2E4A;">Governing Law &amp; Disputes</div></div>
                                        <div style="padding-left:42px;margin-top:8px;">
                                            <div style="display:flex;gap:12px;margin-top:10px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;min-width:32px;">6.1</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">This Agreement shall be governed by and construed in accordance with the laws of the {{ v.agr.jurisdiction }}.</div></div>
                                            <div style="display:flex;gap:12px;margin-top:10px;"><div style="font-family:'IBM Plex Mono',monospace;font-size:12px;color:#9A958B;min-width:32px;">6.2</div><div style="font-size:13px;line-height:1.7;color:rgba(35,38,43,.78);">Any dispute arising out of or in connection with this Agreement shall first be referred to good-faith negotiation, and failing resolution within 30 days, to arbitration in {{ v.agr.venue }} under the applicable arbitration rules.</div></div>
                                        </div>
                                    </div>
                                    <div style="margin-top:34px;padding-top:22px;border-top:1px solid #ECE8E0;">
                                        <div style="font-family:'Cormorant Garamond',serif;font-size:13px;font-style:italic;color:rgba(35,38,43,.7);">In witness whereof the parties have executed this Agreement on the day and year first above written.</div>
                                        <div style="margin-top:30px;display:grid;grid-template-columns:1fr 1fr;gap:40px;">
                                            <div><div style="font-size:10px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:#A8854E;">Signed for the Vendor</div><div style="font-family:'Cormorant Garamond',serif;font-size:30px;font-style:italic;color:#1C2E4A;margin-top:22px;">{{ v.agr.vendorSign }}</div><div style="height:1px;background:#23262B;margin-top:6px;"></div><div style="font-size:12px;font-weight:600;color:#23262B;margin-top:8px;">{{ v.agr.vendorName }}</div><div style="font-size:11px;color:#9A958B;margin-top:2px;">{{ v.agr.vendorSignRole }}</div><div style="font-family:'IBM Plex Mono',monospace;font-size:11px;color:#9A958B;margin-top:10px;">Date&nbsp;&nbsp;____________________</div></div>
                                            <div><div style="font-size:10px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:#A8854E;">Signed by the Purchaser</div><div style="font-family:'Cormorant Garamond',serif;font-size:30px;font-style:italic;color:rgba(28,46,74,.25);margin-top:22px;">&nbsp;</div><div style="height:1px;background:#23262B;margin-top:6px;"></div><div style="font-size:12px;font-weight:600;color:#23262B;margin-top:8px;">{{ v.agr.purchaserName }}</div><div style="font-size:11px;color:#9A958B;margin-top:2px;">Purchaser</div><div style="font-family:'IBM Plex Mono',monospace;font-size:11px;color:#9A958B;margin-top:10px;">Date&nbsp;&nbsp;____________________</div></div>
                                        </div>
                                        <div style="margin-top:34px;display:grid;grid-template-columns:1fr 1fr;gap:40px;">
                                            <div><div style="font-size:10px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:#9A958B;">Witness — Vendor</div><div style="height:1px;background:#D9D4C9;margin-top:30px;"></div><div style="display:flex;justify-content:space-between;margin-top:7px;font-size:10.5px;color:#9A958B;"><span>Name &amp; signature</span><span style="font-family:'IBM Plex Mono',monospace;">Date</span></div></div>
                                            <div><div style="font-size:10px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:#9A958B;">Witness — Purchaser</div><div style="height:1px;background:#D9D4C9;margin-top:30px;"></div><div style="display:flex;justify-content:space-between;margin-top:7px;font-size:10.5px;color:#9A958B;"><span>Name &amp; signature</span><span style="font-family:'IBM Plex Mono',monospace;">Date</span></div></div>
                                        </div>
                                    </div>
                                </div>
                                <div style="margin-top:24px;padding:18px 64px;background:#1C2E4A;color:rgba(244,241,234,.72);display:flex;align-items:center;justify-content:space-between;font-size:11px;letter-spacing:.03em;"><span>{{ v.agr.footerLeft }}</span><span style="font-family:'IBM Plex Mono',monospace;color:#C9A86A;letter-spacing:.1em;">PAGE 03 / 03</span></div>
                            </div>
                        </div>

                        <!-- ===== STANDARD DOCUMENT SHELL ===== -->
                        <div v-else id="doc-paper" style="width:100%;max-width:760px;background:#fff;color:#1b1b1b;border-radius:6px;box-shadow:0 30px 70px rgba(0,0,0,.45);padding:54px 56px 46px;">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #1C2E4A;padding-bottom:18px;gap:14px;flex-wrap:wrap;">
                                <div style="display:flex;align-items:center;"><img :src="LOGO_LIGHT" alt="Bankrol — Construction, Investment, Energy" style="height:40px;width:auto;display:block;" /></div>
                                <div style="text-align:right;font-size:10.5px;color:#777;line-height:1.7;">Plot 124, Cadastral Zone B05, Utako<br />Abuja, FCT, Nigeria<br />RC 1029384 · +234 9 876 5432</div>
                            </div>
                            <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:20px;font-size:11.5px;color:#666;"><div>Ref: <strong style="color:#1b1b1b;">{{ v.doc.ref }}</strong></div><div>Date: <strong style="color:#1b1b1b;">{{ v.doc.date }}</strong></div></div>
                            <div style="font-family:'Cormorant Garamond',serif;font-size:27px;font-weight:600;color:#1C2E4A;margin-top:14px;">{{ v.doc.heading }}</div>

                            <!-- OFFER LETTER -->
                            <template v-if="v.doc.isOffer">
                                <p style="font-size:13px;line-height:1.85;color:#333;margin:20px 0 0;">Dear {{ v.doc.client }},</p>
                                <p style="font-size:13.5px;font-weight:700;color:#1C2E4A;margin:14px 0 0;letter-spacing:.02em;">RE: OFFER OF {{ v.doc.property }}</p>
                                <p style="font-size:13px;line-height:1.85;color:#333;margin:14px 0 0;">We are delighted to offer you {{ v.doc.property }} at {{ v.doc.dev }} for a total consideration of <strong>{{ v.doc.price }}</strong>. This offer remains valid for fourteen (14) days from the date above.</p>
                                <p style="font-size:13px;line-height:1.85;color:#333;margin:14px 0 12px;">The indicative payment structure is set out below:</p>
                                <div v-for="(r, i) in v.doc.schedule" :key="i" style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #eee;font-size:12.5px;"><span style="color:#444;">{{ r.k }}</span><span style="font-weight:600;color:#1b1b1b;">{{ r.v }}</span></div>
                                <p style="font-size:13px;line-height:1.85;color:#333;margin:16px 0 0;">Kindly signify your acceptance by countersigning a copy of this letter. We look forward to welcoming you to the Bankrol family.</p>
                                <div style="margin-top:40px;font-size:13px;color:#333;">Yours faithfully,<div style="font-family:'Cormorant Garamond',serif;font-size:26px;color:#1C2E4A;margin-top:14px;">Adunni Okafor</div><div style="font-size:11.5px;color:#777;margin-top:2px;">Managing Director · Bankrol</div></div>
                            </template>

                            <!-- ALLOCATION LETTER -->
                            <template v-if="v.doc.isAllocation">
                                <p style="font-size:13px;line-height:1.85;color:#333;margin:20px 0 0;">Dear {{ v.doc.client }},</p>
                                <p style="font-size:13.5px;font-weight:700;color:#1C2E4A;margin:14px 0 0;letter-spacing:.02em;">RE: ALLOCATION OF {{ v.doc.property }}</p>
                                <p style="font-size:13px;line-height:1.85;color:#333;margin:14px 0 14px;">Following your acceptance of our offer and the receipt of your reservation deposit, we are pleased to formally allocate the unit detailed below to you.</p>
                                <div v-for="(r, i) in v.doc.allocation" :key="i" style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #eee;font-size:12.5px;"><span style="color:#444;">{{ r.k }}</span><span style="font-weight:600;color:#1b1b1b;">{{ r.v }}</span></div>
                                <p style="font-size:13px;line-height:1.85;color:#333;margin:16px 0 0;">Our documentation team will be in touch regarding the transfer of title and the handover schedule.</p>
                                <div style="margin-top:40px;font-size:13px;color:#333;">Sincerely,<div style="font-family:'Cormorant Garamond',serif;font-size:26px;color:#1C2E4A;margin-top:14px;">Adunni Okafor</div><div style="font-size:11.5px;color:#777;margin-top:2px;">Managing Director · Bankrol</div></div>
                            </template>

                            <!-- COMPLETION CERTIFICATE -->
                            <template v-if="v.doc.isCompletion">
                                <div style="text-align:center;margin-top:26px;">
                                    <div style="font-size:11px;font-weight:700;letter-spacing:.24em;text-transform:uppercase;color:#A8854E;">This is to certify that</div>
                                    <div style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:600;color:#1C2E4A;margin-top:14px;line-height:1.2;">{{ v.doc.property }}</div>
                                    <div style="font-size:13px;color:#666;margin-top:6px;">{{ v.doc.dev }}</div>
                                    <p style="font-size:13px;line-height:1.85;color:#333;max-width:520px;margin:20px auto 0;">has been constructed and completed by Bankrol in accordance with the approved architectural plans, structural specifications and the standards of Bankrol Limited.</p>
                                </div>
                                <div style="display:flex;justify-content:center;gap:40px;flex-wrap:wrap;margin-top:26px;">
                                    <div v-for="(r, i) in v.doc.completion" :key="i" style="text-align:center;"><div style="font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#999;">{{ r.k }}</div><div style="font-size:13.5px;font-weight:600;color:#1b1b1b;margin-top:5px;">{{ r.v }}</div></div>
                                </div>
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:48px;gap:20px;">
                                    <div style="flex:1;"><div style="border-top:1px solid #333;padding-top:6px;font-size:11.5px;color:#555;max-width:200px;">{{ v.doc.engineer }} — Supervising Engineer</div></div>
                                    <div style="width:84px;height:84px;border:2px solid #A8854E;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#A8854E;flex:none;"><span style="font-family:'Material Symbols Rounded';font-size:38px;">workspace_premium</span></div>
                                    <div style="flex:1;text-align:right;"><div style="border-top:1px solid #333;padding-top:6px;font-size:11.5px;color:#555;max-width:200px;margin-left:auto;">Managing Director — Bankrol Limited</div></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<style>
:root {
    --bg: #ECE9E2; --grad: radial-gradient(1100px 540px at 80% -14%, rgba(168,133,78,0.10), transparent 62%);
    --card: #FFFFFF; --card2: #FAF8F3; --border: #ECE8E0; --line: #F1EDE5;
    --text: #23262B; --t2: rgba(35,38,43,.5); --t3: #9A958B;
    --gold: #A8854E; --goldb: #C9A86A; --golds: rgba(168,133,78,.12);
    --navy: #1C2E4A; --navyt: #1C2E4A; --green: #5E7355; --greens: rgba(94,115,85,.10);
    --amber: #C28A2E; --ambers: rgba(194,138,46,.13);
    --alert: #B5683F; --alerts: rgba(181,104,63,.10); --steel: #7E93A8; --steels: rgba(126,147,168,.14);
    --shadow: 0 1px 2px rgba(28,32,40,.03),0 10px 30px rgba(28,32,40,.045);
    --rail: 236px;
}
[data-theme="dark"] {
    --bg: #0A0B0D; --grad: radial-gradient(1100px 560px at 76% -12%, rgba(201,168,106,0.07), transparent 60%);
    --card: #121319; --card2: #16181F; --border: rgba(255,255,255,.07); --line: rgba(255,255,255,.06);
    --text: #F4F1EA; --t2: rgba(244,241,234,.5); --t3: rgba(244,241,234,.46);
    --gold: #C9A86A; --goldb: #E3CB97; --golds: rgba(201,168,106,.12);
    --navy: #22375A; --navyt: #E3CB97; --green: #9DB08C; --greens: rgba(157,176,140,.10);
    --amber: #D9A94E; --ambers: rgba(217,169,78,.14);
    --alert: #C98C72; --alerts: rgba(201,140,114,.12); --steel: #7E93A8; --steels: rgba(126,147,168,.16);
    --shadow: 0 1px 2px rgba(0,0,0,.25),0 14px 36px rgba(0,0,0,.36);
}
.bnk-root * { box-sizing: border-box; }
@keyframes fadeRise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
@keyframes softPulse { 0%,100% { opacity: .5; } 50% { opacity: 1; } }
@keyframes drawerIn { from { transform: translateX(40px); opacity: 0; } to { transform: none; opacity: 1; } }
@media (prefers-reduced-motion: reduce) { .bnk-root * { animation: none !important; } }
.bnk-root ::-webkit-scrollbar { width: 9px; height: 9px; }
.bnk-root ::-webkit-scrollbar-thumb { background: rgba(128,128,128,.28); border-radius: 8px; }
.bnk-root ::-webkit-scrollbar-track { background: transparent; }
.bnk-rowhover:hover { background: var(--card2) !important; }
.bnk-cardhover:hover { border-color: var(--gold) !important; }
/* Premium, sharp navigation icons */
.nav-ic { font-family: 'Material Symbols Sharp'; font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; }
#shell { display: grid; grid-template-columns: var(--rail) 1fr; min-height: 100vh; }
#bottomnav { display: none; }
@media (max-width: 880px) {
    #sidebar { display: none !important; }
    #shell { grid-template-columns: 1fr !important; }
    #bottomnav { display: flex !important; }
    #content { padding: 16px 14px 100px !important; }
    #topbar-search { display: none !important; }
    #login-art { display: none !important; }
    .bnk-login-grid { grid-template-columns: 1fr !important; }
    #doc-paper:not(.bnk-rc):not(.bnk-agr) { padding: 30px 20px !important; }
    #drawer-panel { max-width: 100% !important; }
}
/* Payment receipt — responsive so nothing clips on narrow screens */
.bnk-rc .rc-amt { overflow-wrap: anywhere; }
@media (max-width: 720px) {
    .bnk-rc .rc-x { padding-left: 22px !important; padding-right: 22px !important; }
    .bnk-rc .rc-hx { margin-left: 22px !important; margin-right: 22px !important; }
    .bnk-rc .rc-hero { margin-left: 18px !important; margin-right: 18px !important; padding-left: 20px !important; padding-right: 20px !important; }
    .bnk-rc .rc-grid { grid-template-columns: 1fr !important; }
    .bnk-rc .rc-amt { font-size: 32px !important; }
    .bnk-rc .inv-grid { grid-template-columns: 1fr auto !important; }
    .bnk-rc .inv-sec { display: none !important; }
}
/* Sale agreement — keep the payment table scrollable, never clipped */
.bnk-agr [style*="34px 1fr 150px 96px"] { min-width: 520px; }
@media (max-width: 760px) {
    .bnk-agr [style*="padding:64px 64px"] { padding-left: 22px !important; padding-right: 22px !important; }
    .bnk-agr [style*="padding:40px 64px"] { padding-left: 22px !important; padding-right: 22px !important; }
    .bnk-agr [style*="18px 64px"] { padding-left: 22px !important; padding-right: 22px !important; }
    .bnk-agr [style*="padding-left:42px"] { padding-left: 14px !important; }
    .bnk-agr [style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
    .bnk-agr [style*="border-radius:10px;overflow:hidden"] { overflow: auto !important; }
}
@media print {
    @page { size: A4 portrait; margin: 12mm; }
    /* Show only the document, in normal flow so multi-page content paginates */
    #shell > *:not(#doc-modal) { display: none !important; }
    #doc-modal { position: static !important; inset: auto !important; z-index: auto !important; background: #fff !important; display: block !important; overflow: visible !important; padding: 0 !important; }
    #doc-toolbar { display: none !important; }
    #doc-scroll { display: block !important; overflow: visible !important; padding: 0 !important; height: auto !important; }
    #doc-paper { position: static !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; overflow: visible !important; box-shadow: none !important; border-radius: 0 !important; }
    #doc-paper, #doc-paper * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    /* Multi-page agreement: one sheet per page */
    #doc-paper.bnk-agr { gap: 0 !important; }
    .bnk-page { box-shadow: none !important; border-radius: 0 !important; overflow: visible !important; min-height: auto !important; break-after: page; page-break-after: always; }
    .bnk-page:last-child { break-after: auto; page-break-after: auto; }
}
</style>
