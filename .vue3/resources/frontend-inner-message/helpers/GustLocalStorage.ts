class GustLocalStorage {
	static key = '__gust_medias';

	static getItems() {
		const localStorageData = localStorage.getItem(GustLocalStorage.key);
		return localStorageData ? JSON.parse(localStorageData) : {};
	}

	static getItem(key: string) {
		const data = GustLocalStorage.getItems();
		return data[key] ?? null;
	}

	static setItems(newData: Record<string, undefined>) {
		const data = GustLocalStorage.getItems();
		Object.entries(newData).forEach(([key, value]) => {
			data[key] = value;
		})
		localStorage.setItem(GustLocalStorage.key, JSON.stringify(data));
	}

	static setItem(key: string, value: undefined) {
		const data = GustLocalStorage.getItems();
		data[key] = value;
		localStorage.setItem(GustLocalStorage.key, JSON.stringify(data));
	}

	static removeItem(key: string) {
		const data = GustLocalStorage.getItems();
		if (data[key]) {
			delete data[key];
			localStorage.setItem(GustLocalStorage.key, JSON.stringify(data));
		}
	}

	static getMedia() {
		return GustLocalStorage.getItem('media_ids') || [];
	}

	static appendMedia(id: string | number) {
		const ids = GustLocalStorage.getItem('media_ids') || [];
		ids.push(id);
		GustLocalStorage.setItem('media_ids', ids);
	}
}

class GustVideoStorage {
	static key = '__gust_videos';

	static getItems() {
		const localStorageData = localStorage.getItem(GustVideoStorage.key);
		return localStorageData ? JSON.parse(localStorageData) : {};
	}

	static getItem(key: string) {
		const data = GustVideoStorage.getItems();
		return data[key] ?? null;
	}

	static setItems(newData: Record<string, undefined>) {
		const data = GustVideoStorage.getItems();
		Object.entries(newData).forEach(([key, value]) => {
			data[key] = value;
		})
		localStorage.setItem(GustVideoStorage.key, JSON.stringify(data));
	}

	static setItem(key: string, value: undefined) {
		const data = GustVideoStorage.getItems();
		data[key] = value;
		localStorage.setItem(GustVideoStorage.key, JSON.stringify(data));
	}

	static removeItem(key: string) {
		const data = GustVideoStorage.getItems();
		if (data[key]) {
			delete data[key];
			localStorage.setItem(GustVideoStorage.key, JSON.stringify(data));
		}
	}

	static getMedia() {
		return GustVideoStorage.getItem('media_ids') || [];
	}

	static appendMedia(id: string | number) {
		const ids = GustVideoStorage.getItem('media_ids') || [];
		ids.push(id);
		GustVideoStorage.setItem('media_ids', ids);
	}
}

export {GustVideoStorage, GustLocalStorage};
export default GustLocalStorage;
